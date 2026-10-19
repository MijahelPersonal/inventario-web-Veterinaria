<?php
declare(strict_types=1);
namespace App\Service;
use App\Model\Administration;
use DomainException;
use PDO;
use PDOException;

// Valida configuración y cuentas, conservando al menos un administrador activo.
final class AdministrationService
{
    public function __construct(private PDO $db) {}
    public function settings(array $input, int $user): void
    {
        $color = (string) ($input['primary_color'] ?? '');
        $theme = (string) ($input['theme'] ?? 'light');
        $palette = require dirname(__DIR__, 3) . '/config/appearance.php';
        if (!isset($palette[$color]) || !in_array($theme, ['light', 'dark'], true)) {
            throw new DomainException('Selecciona un color y un tema disponibles.');
        }
        $data = ['primary_color' => $color, 'theme' => $theme];
        $this->db->beginTransaction();
        try {
            $model = new Administration($this->db);
            foreach ($data as $key => $value) {
                $model->setSetting($key, $value);
            }
            (new AuditService($this->db))->record($user, 'settings', 'settings');
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
    public function user(array $input, ?int $id, int $actor): int
    {
        $name = trim((string) ($input['name'] ?? ''));
        $username = mb_strtolower(trim((string) ($input['username'] ?? '')));
        $email = trim((string) ($input['email'] ?? '')) ?: null;
        $password = (string) ($input['password'] ?? '');
        $active = isset($input['active']) ? 1 : 0;
        if (
            $name === '' ||
            mb_strlen($name) > 120 ||
            !preg_match('/^[a-z0-9._-]{3,60}$/D', $username) ||
            ($email && (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)))
        ) {
            throw new DomainException('Nombre, usuario o correo inválidos.');
        }
        if ((!$id || $password !== '') && (mb_strlen($password) < 8 || strlen($password) > 72)) {
            throw new DomainException('Contraseña: mínimo 8 caracteres y máximo 72 bytes.');
        }
        if (!is_array($input['roles'] ?? null)) {
            throw new DomainException('Selecciona al menos un rol.');
        }
        $roles = array_values(array_unique(array_map('intval', $input['roles'])));
        $model = new Administration($this->db);
        $valid = array_column($model->roles(), 'id');
        if (!$roles || array_diff($roles, $valid)) {
            throw new DomainException('Rol desconocido.');
        }
        if ($id === $actor && !$active) {
            throw new DomainException('No puedes desactivar tu propia cuenta.');
        }
        $this->db->beginTransaction();
        try {
            // El bloqueo común evita que dos cambios eliminen simultáneamente los últimos administradores.
            $model->lockRoles();
            $old = $id ? $model->findUser($id, true) : null;
            if ($id && !$old) {
                throw new DomainException('El usuario no existe.');
            }
            $saved = $model->saveUser(
                ['name' => $name, 'username' => $username, 'email' => $email, 'active' => $active],
                $id,
                $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
                $roles,
            );
            if ($model->activeAdministrators() < 1) {
                throw new DomainException('Debe quedar al menos un administrador activo.');
            }
            (new AuditService($this->db))->record(
                $actor,
                $id ? 'update' : 'create',
                'users',
                $saved,
            );
            $this->db->commit();
            return $saved;
        } catch (PDOException $error) {
            $this->db->rollBack();
            if ($error->getCode() === '23000') {
                throw new DomainException('Ese usuario o correo ya existe.');
            }
            throw $error;
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
