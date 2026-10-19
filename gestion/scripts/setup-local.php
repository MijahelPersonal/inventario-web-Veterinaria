<?php
declare(strict_types=1);
// Solo para preparar una instalación NUEVA en Laragon. Nunca modifica bases existentes.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
$root = dirname(__DIR__);
if (is_file($root . '/config/local.php')) {
    fwrite(STDERR, "Ya existe configuración local. No se reemplazará.\n");
    exit(1);
}
$admin = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
foreach (['struch_vet', 'struch_vet_test'] as $name) {
    $query = $admin->prepare(
        'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME=?',
    );
    $query->execute([$name]);
    if ($query->fetch()) {
        fwrite(STDERR, "La base $name ya existe. Detenido para no modificarla.\n");
        exit(1);
    }
}
$password = bin2hex(random_bytes(24));
$admin->exec("CREATE USER 'struch_vet_app'@'127.0.0.1' IDENTIFIED BY " . $admin->quote($password));
foreach (['struch_vet', 'struch_vet_test'] as $name) {
    $admin->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
    $admin->exec("GRANT ALL PRIVILEGES ON `$name`.* TO 'struch_vet_app'@'127.0.0.1'");
    $settings = [
        'database' => [
            'host' => '127.0.0.1',
            'port' => 3306,
            'name' => $name,
            'username' => 'struch_vet_app',
            'password' => $password,
        ],
    ];
    $file = $name === 'struch_vet' ? 'local.php' : 'local.test.php';
    file_put_contents(
        $root . '/config/' . $file,
        "<?php\nreturn " . var_export($settings, true) . ";\n",
    );
}
require $root . '/scripts/migrate.php';
$config = require $root . '/config/app.php';
$db = Core\Database::connect($config['database']);
$initialPassword = bin2hex(random_bytes(10));
$db->prepare('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)')->execute([
    'Administrador',
    'admin@struchvet.local',
    password_hash($initialPassword, PASSWORD_DEFAULT),
]);
$id = (int) $db->lastInsertId();
$db->prepare(
    "INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE name='Administrador'",
)->execute([$id]);
new App\Service\AuditService($db)->record($id, 'create_admin', 'users', $id);
if (!is_dir($root . '/storage/private')) {
    mkdir($root . '/storage/private', 0700, true);
}
file_put_contents(
    $root . '/storage/private/acceso-inicial.txt',
    "STRUCH VET — acceso local\nCorreo: admin@struchvet.local\nContraseña: $initialPassword\nNo compartir ni subir este archivo a GitHub.\n",
);
echo "Preparación terminada. Acceso inicial guardado en storage/private/acceso-inicial.txt (excluido de Git).\n";
