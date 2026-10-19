<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
ini_set('session.save_path', dirname(__DIR__) . '/storage/cache');
Core\Session::start();
$email = 'access-' . bin2hex(random_bytes(5));
$password = bin2hex(random_bytes(12));
$db->prepare('INSERT INTO users(name,username,password_hash) VALUES (?,?,?)')->execute([
    'Prueba',
    $email,
    password_hash($password, PASSWORD_DEFAULT),
]);
$id = (int) $db->lastInsertId();
$db->prepare(
    "INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE name='Administrador'",
)->execute([$id]);
$auth = new App\Service\AuthService($db);
$check = static function (bool $valid, string $message): void {
    if (!$valid) {
        throw new RuntimeException($message);
    }
};
try {
    try {
        $auth->login($email, 'incorrecta');
        throw new RuntimeException('Aceptó contraseña inválida');
    } catch (DomainException) {
    }
    $auth->login($email, $password);
    $check($auth->current()['id'] === $id, 'Login falló');
    $check(in_array('*', $auth->current()['permissions'], true), 'Permisos faltantes');
    $_SESSION['last_activity'] = time() - 1801;
    $check($auth->current() === null, 'No venció la sesión');
    $auth->login($email, $password);
    $auth->logout();
    $check($auth->current() === null, 'Logout falló');
    for ($i = 0; $i < 5; $i++) {
        try {
            $auth->login($email, 'incorrecta');
        } catch (DomainException) {
        }
    }
    try {
        $auth->login($email, $password);
        throw new RuntimeException('No limitó intentos');
    } catch (DomainException $error) {
        $check(str_contains($error->getMessage(), '15 minutos'), 'Límite incorrecto');
    }
    $output = "OK: login, permisos, inactividad, logout y límite de intentos.\n";
} finally {
    $db->prepare('DELETE FROM audit_events WHERE user_id=?')->execute([$id]);
    $db->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$id]);
    $db->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
    $db->prepare('DELETE FROM login_attempts WHERE attempt_key=?')->execute([
        hash('sha256', $email . '|cli'),
    ]);
    session_destroy();
}
echo $output;
