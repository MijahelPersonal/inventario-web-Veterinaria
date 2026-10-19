<?php
declare(strict_types=1);
namespace App\Service;
use App\Model\User;
use Core\Session;
use DomainException;
use PDO;
// Verifica credenciales, limita intentos y controla la vigencia de las sesiones.
final class AuthService
{
    public function __construct(private PDO $db) {}
    public function current(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        if (time() - ($_SESSION['last_activity'] ?? 0) > 1800) {
            unset($_SESSION['user_id']);
            return null;
        }
        $user = (new User($this->db))->byId((int) $_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id']);
            return null;
        }
        $_SESSION['last_activity'] = time();
        $user['permissions'] = (new User($this->db))->permissions((int) $user['id']);
        return $user;
    }
    public function login(string $username, string $password): void
    {
        $username = mb_strtolower(trim($username));
        if (!preg_match('/^[a-z0-9._-]{3,60}$/D', $username)) {
            throw new DomainException('Usuario o contraseña incorrectos.');
        }
        $key = hash('sha256', $username . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        $this->db->beginTransaction();
        try {
            $this->db
                ->prepare(
                    'INSERT IGNORE INTO login_attempts (attempt_key,attempts,window_start) VALUES (?,0,UTC_TIMESTAMP())',
                )
                ->execute([$key]);
            $query = $this->db->prepare(
                'SELECT *,TIMESTAMPDIFF(SECOND,window_start,UTC_TIMESTAMP()) age FROM login_attempts WHERE attempt_key=? FOR UPDATE',
            );
            $query->execute([$key]);
            $attempt = $query->fetch();
            if ((int) $attempt['age'] >= 900) {
                $this->db
                    ->prepare(
                        'UPDATE login_attempts SET attempts=0,window_start=UTC_TIMESTAMP() WHERE attempt_key=?',
                    )
                    ->execute([$key]);
                $attempt['attempts'] = 0;
            }
            if ((int) $attempt['attempts'] >= 5) {
                $this->db->commit();
                throw new DomainException('Demasiados intentos. Espera 15 minutos.');
            }
            $user = (new User($this->db))->byUsername($username);
            $valid = password_verify(
                $password,
                $user['password_hash'] ??
                    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
            );
            if (!$user || !$valid) {
                $this->db
                    ->prepare('UPDATE login_attempts SET attempts=attempts+1 WHERE attempt_key=?')
                    ->execute([$key]);
                $this->db->commit();
                throw new DomainException('Usuario o contraseña incorrectos.');
            }
            $this->db->prepare('DELETE FROM login_attempts WHERE attempt_key=?')->execute([$key]);
            (new AuditService($this->db))->record(
                (int) $user['id'],
                'login',
                'users',
                (int) $user['id'],
            );
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    public function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            (new AuditService($this->db))->record(
                (int) $_SESSION['user_id'],
                'logout',
                'users',
                (int) $_SESSION['user_id'],
            );
        }
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        Session::flash('Sesión cerrada.');
    }
}
