<?php
declare(strict_types=1);
namespace App\Model;
use PDO;

// Accede a personal y preferencias sin permitir borrar usuarios con historial.
final class Administration
{
    public function __construct(private PDO $db) {}
    public function settings(): array
    {
        return $this->db
            ->query('SELECT setting_key,setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    public function setSetting(string $key, string $value): void
    {
        $this->db
            ->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?')
            ->execute([$value, $key]);
    }
    public function users(): array
    {
        return $this->db
            ->query(
                'SELECT u.id,u.name,u.username,u.email,u.active,GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ", ") role_names FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r ON r.id=ur.role_id GROUP BY u.id ORDER BY u.name',
            )
            ->fetchAll();
    }
    public function roles(): array
    {
        return $this->db->query('SELECT * FROM roles ORDER BY id')->fetchAll();
    }
    public function findUser(int $id, bool $lock = false): ?array
    {
        $query = $this->db->prepare(
            'SELECT id,name,username,email,active FROM users WHERE id=?' .
                ($lock ? ' FOR UPDATE' : ''),
        );
        $query->execute([$id]);
        $user = $query->fetch();
        if (!$user) {
            return null;
        }
        $query = $this->db->prepare('SELECT role_id FROM user_roles WHERE user_id=?');
        $query->execute([$id]);
        $user['roles'] = $query->fetchAll(PDO::FETCH_COLUMN);
        return $user;
    }
    public function lockRoles(): void
    {
        $this->db->query('SELECT id FROM roles ORDER BY id FOR UPDATE')->fetchAll();
    }
    public function activeAdministrators(): int
    {
        return (int) $this->db
            ->query(
                "SELECT COUNT(*) FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.active=1 AND r.name='Administrador'",
            )
            ->fetchColumn();
    }
    public function saveUser(array $data, ?int $id, ?string $hash, array $roles): int
    {
        if ($id) {
            $this->db
                ->prepare('UPDATE users SET name=?,username=?,email=?,active=? WHERE id=?')
                ->execute([...array_values($data), $id]);
            if ($hash) {
                $this->db
                    ->prepare('UPDATE users SET password_hash=? WHERE id=?')
                    ->execute([$hash, $id]);
            }
        } else {
            $this->db
                ->prepare(
                    'INSERT INTO users(name,username,email,active,password_hash) VALUES (?,?,?,?,?)',
                )
                ->execute([...array_values($data), $hash]);
            $id = (int) $this->db->lastInsertId();
        }
        $this->db->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$id]);
        foreach ($roles as $role) {
            $this->db
                ->prepare('INSERT INTO user_roles(user_id,role_id) VALUES (?,?)')
                ->execute([$id, $role]);
        }
        return $id;
    }
    public function audit(): array
    {
        return $this->db
            ->query(
                'SELECT a.*,u.username FROM audit_events a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 100',
            )
            ->fetchAll();
    }
}
