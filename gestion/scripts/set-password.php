<?php
declare(strict_types=1);

// Restablece una contraseña desde la consola sin guardarla en el código ni en argumentos.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

$config = require dirname(__DIR__) . '/config/bootstrap.php';
$username = mb_strtolower(trim($argv[1] ?? ''));
if (!preg_match('/^[a-z0-9._-]{3,60}$/D', $username)) {
    fwrite(STDERR, "Uso: php scripts/set-password.php usuario\n");
    exit(1);
}

fwrite(STDOUT, 'Nueva contraseña (mínimo 8 caracteres; la terminal puede mostrarla): ');
$password = rtrim((string) fgets(STDIN), "\r\n");
if (mb_strlen($password) < 8 || strlen($password) > 72) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres y como máximo 72 bytes.\n");
    exit(1);
}

$db = Core\Database::connect($config['database']);
$db->beginTransaction();
try {
    $query = $db->prepare('SELECT id FROM users WHERE username=? AND active=1 FOR UPDATE');
    $query->execute([$username]);
    $id = $query->fetchColumn();
    if (!$id) {
        throw new RuntimeException('El usuario activo no existe.');
    }

    $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([
        password_hash($password, PASSWORD_DEFAULT),
        $id,
    ]);
    $account = (new App\Model\User($db))->byUsername($username);
    if (!$account || !password_verify($password, $account['password_hash'])) {
        throw new RuntimeException('No se pudo verificar la contraseña guardada.');
    }
    (new App\Service\AuditService($db))->record(null, 'password_reset_cli', 'users', (int) $id);
    $db->commit();
    echo "\nContraseña actualizada.\n";
} catch (Throwable $error) {
    $db->rollBack();
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
