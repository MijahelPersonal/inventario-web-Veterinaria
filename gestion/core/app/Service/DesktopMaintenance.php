<?php
declare(strict_types=1);
namespace App\Service;
use Core\Paths;
use PDO;
use RuntimeException;

// Operaciones explícitas de instalación; nunca se invocan al atender páginas web.
final class DesktopMaintenance
{
    public function __construct(private PDO $db, private string $root) {}
    private function tables(): array
    {
        return $this->db->query('SHOW TABLE STATUS')->fetchAll();
    }
    public function status(): array
    {
        $tables = array_column($this->tables(), 'Name');
        $applied = in_array('schema_migrations', $tables, true)
            ? $this->db->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN)
            : [];
        $available = array_map('basename', glob($this->root . '/database/migrations/*.sql'));
        $pending = array_values(array_diff($available, $applied));
        $admin = false;
        if (count(array_intersect(['users', 'roles', 'user_roles'], $tables)) === 3) {
            $admin = (bool) $this->db
                ->query(
                    "SELECT COUNT(*) FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.active=1 AND r.name='Administrador'",
                )
                ->fetchColumn();
        }
        return [
            'ok' => true,
            'connected' => true,
            'initialized' => count($tables) > 0,
            'pending' => $pending,
            'admin_exists' => $admin,
            'recovery_required' => is_file(Paths::storage('private/migration-attempt.json')),
            'compatible' => array_diff($applied, $available) === [],
            'newer_migrations' => array_values(array_diff($applied, $available)),
        ];
    }
    public function backup(): string
    {
        $tables = $this->tables();
        foreach ($tables as $table) {
            if (($table['Engine'] ?? '') !== 'InnoDB') {
                throw new RuntimeException(
                    'El respaldo requiere tablas InnoDB y ausencia de vistas. Solicita un respaldo supervisado del servidor.',
                );
            }
        }
        $directory = dirname(Paths::storage()) . '/backups';
        if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
            throw new RuntimeException('No se pudo preparar la carpeta de respaldos.');
        }
        $path =
            $directory . '/struchvet-' . date('Ymd-His') . '-' . bin2hex(random_bytes(5)) . '.sql';
        $temporary = $path . '.partial';
        $handle = fopen($temporary, 'xb');
        if (!$handle) {
            throw new RuntimeException('No se pudo crear el respaldo.');
        }
        chmod($temporary, 0600);
        $write = static function (string $value) use ($handle): void {
            if (fwrite($handle, $value) !== strlen($value)) {
                throw new RuntimeException('No hay espacio suficiente para terminar el respaldo.');
            }
        };
        $rows = null;
        $buffered = $this->db->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        try {
            $timeZone = $this->db->quote(
                (string) $this->db->query('SELECT @@session.time_zone')->fetchColumn(),
            );
            $sqlMode = $this->db->quote(
                (string) $this->db->query('SELECT @@session.sql_mode')->fetchColumn(),
            );
            $this->db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $this->db->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
            $write(
                '-- Respaldo StruchVet Desktop: ' .
                    date(DATE_ATOM) .
                    "\n-- Restaurar en una base nueva, con la aplicación detenida; comprobar antes de sustituir datos.\nSET NAMES utf8mb4;\nSET time_zone=" .
                    $timeZone .
                    ";\nSET sql_mode=" .
                    $sqlMode .
                    ";\nSET FOREIGN_KEY_CHECKS=0;\n",
            );
            foreach ($tables as $table) {
                $identifier = '`' . str_replace('`', '``', $table['Name']) . '`';
                $create = $this->db
                    ->query('SHOW CREATE TABLE ' . $identifier)
                    ->fetch(PDO::FETCH_NUM)[1];
                $write("\n" . $create . ";\n");
                $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                $rows = $this->db->query('SELECT * FROM ' . $identifier);
                while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                    $columns = implode(
                        ',',
                        array_map(
                            static fn(string $key): string => '`' .
                                str_replace('`', '``', $key) .
                                '`',
                            array_keys($row),
                        ),
                    );
                    $values = implode(
                        ',',
                        array_map(
                            fn($value): string => $value === null
                                ? 'NULL'
                                : $this->db->quote((string) $value),
                            $row,
                        ),
                    );
                    $write(
                        'INSERT INTO ' .
                            $identifier .
                            ' (' .
                            $columns .
                            ') VALUES (' .
                            $values .
                            ");\n",
                    );
                }
                $rows->closeCursor();
                $rows = null;
                $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buffered);
            }
            $write("\nSET FOREIGN_KEY_CHECKS=1;\n");
            $this->db->commit();
            fflush($handle);
            if (function_exists('fsync')) {
                fsync($handle);
            }
            fclose($handle);
            if (!rename($temporary, $path)) {
                throw new RuntimeException('No se pudo finalizar el respaldo.');
            }
            return $path;
        } catch (\Throwable $error) {
            if ($rows) {
                $rows->closeCursor();
            }
            $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buffered);
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_file($temporary)) {
                unlink($temporary);
            }
            throw $error;
        }
    }
    public function migrate(bool $confirmed): array
    {
        if (!$confirmed) {
            throw new \DomainException(
                'Confirma la instalación o actualización de tablas antes de continuar.',
            );
        }
        if (!$this->status()['compatible']) {
            throw new \DomainException(
                'Esta base fue actualizada por una versión más reciente de Gestión. Actualiza StruchVet Desktop antes de continuar.',
            );
        }
        if ($this->status()['recovery_required']) {
            throw new \DomainException(
                'Una migración anterior quedó incompleta. No se reintentará automáticamente: solicita revisión y recuperación desde el respaldo.',
            );
        }
        $lock = (int) $this->db
            ->query("SELECT GET_LOCK(CONCAT(DATABASE(),':struchvet_migrations'),5)")
            ->fetchColumn();
        if ($lock !== 1) {
            throw new RuntimeException('Otra instancia está actualizando esta base de datos.');
        }
        $backup = null;
        try {
            $status = $this->status();
            if (!$status['pending']) {
                return $status + ['backup' => null, 'applied' => []];
            }
            if ($status['initialized']) {
                $backup = $this->backup();
            }
            $journal = Paths::storage('private/migration-attempt.json');
            $this->db->exec(
                'CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(190) PRIMARY KEY,applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB',
            );
            $applied = [];
            foreach ($status['pending'] as $name) {
                // MySQL DDL no tiene rollback general. Un fallo deja un diario para revisión, sin reintentos silenciosos.
                file_put_contents(
                    $journal,
                    json_encode(
                        ['migration' => $name, 'backup' => $backup, 'started' => date(DATE_ATOM)],
                        JSON_THROW_ON_ERROR,
                    ),
                );
                chmod($journal, 0600);
                $this->db->exec(file_get_contents($this->root . '/database/migrations/' . $name));
                $this->db
                    ->prepare('INSERT INTO schema_migrations(name) VALUES (?)')
                    ->execute([$name]);
                $applied[] = $name;
                unlink($journal);
            }
            return $this->status() + ['backup' => $backup, 'applied' => $applied];
        } finally {
            $this->db->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(),':struchvet_migrations'))");
        }
    }
    public function createAdmin(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $username = mb_strtolower(trim((string) ($input['username'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $email = trim((string) ($input['email'] ?? '')) ?: null;
        if (
            $name === '' ||
            mb_strlen($name) > 120 ||
            !preg_match('/^[a-z0-9._-]{3,60}$/D', $username) ||
            mb_strlen($password) < 12 ||
            strlen($password) > 1024 ||
            ($email && (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)))
        ) {
            throw new \DomainException(
                'Completa nombre, usuario válido y contraseña de al menos 12 caracteres. El correo es opcional.',
            );
        }
        $status = $this->status();
        if ($status['pending'] || $status['recovery_required']) {
            throw new \DomainException('Completa las migraciones antes de crear el administrador.');
        }
        $lock = (int) $this->db
            ->query("SELECT GET_LOCK(CONCAT(DATABASE(),':struchvet_first_admin'),5)")
            ->fetchColumn();
        if ($lock !== 1) {
            throw new RuntimeException('Otra instancia está configurando el administrador.');
        }
        try {
            $this->db->beginTransaction();
            // No permite tomar control de una base existente que tenga usuarios, aunque estén desactivados.
            if ((int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
                throw new \DomainException(
                    'Esta base ya tiene usuarios. Inicia sesión con una cuenta existente.',
                );
            }
            $this->db
                ->prepare('INSERT INTO users(name,username,email,password_hash) VALUES (?,?,?,?)')
                ->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $id = (int) $this->db->lastInsertId();
            $this->db
                ->prepare(
                    "INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE name='Administrador'",
                )
                ->execute([$id]);
            (new AuditService($this->db))->record($id, 'create_admin', 'users', $id);
            $this->db->commit();
            return ['ok' => true, 'created' => true];
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        } finally {
            $this->db->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(),':struchvet_first_admin'))");
        }
    }
}
