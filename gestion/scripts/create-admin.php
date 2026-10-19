<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$name = trim(readline('Nombre: ')); $email = mb_strtolower(trim(readline('Correo: ')));
fwrite(STDOUT,'Contraseña (mínimo 12 caracteres; la terminal puede mostrarla): ');
$password = rtrim(fgets(STDIN),"\r\n");
if ($name === '' || !filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 12) { fwrite(STDERR,"Datos inválidos.\n"); exit(1); }
$db = Core\Database::connect($config['database']); $db->beginTransaction();
try {
    $db->prepare('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)')->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
    $id = (int) $db->lastInsertId();
    $db->prepare("INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE name='Administrador'")->execute([$id]);
    (new App\Service\AuditService($db))->record($id,'create_admin','users',$id);
    $db->commit(); echo "Administrador creado.\n";
} catch (Throwable $error) { $db->rollBack(); throw $error; }
