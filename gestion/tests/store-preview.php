<?php
declare(strict_types=1);
// Datos temporales únicamente en la base de pruebas para verificar el carrito en el navegador.
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$file = dirname(__DIR__) . '/storage/private/pos-preview.json';
if (($argv[1] ?? '') === 'clean') {
    if (!is_file($file)) {
        exit();
    }
    $ids = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM audit_events WHERE user_id=?')->execute([$ids['user']]);
        $db->prepare('DELETE FROM login_attempts WHERE attempt_key=?')->execute([
            hash('sha256', $ids['username'] . '|127.0.0.1'),
        ]);
        $db->prepare('DELETE FROM stock_lots WHERE product_id=?')->execute([$ids['product']]);
        $db->prepare('DELETE FROM products WHERE id=?')->execute([$ids['product']]);
        $db->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$ids['user']]);
        $db->prepare('DELETE FROM users WHERE id=?')->execute([$ids['user']]);
        $db->commit();
        unlink($file);
        echo "Vista de prueba limpiada.\n";
    } catch (Throwable $error) {
        $db->rollBack();
        throw $error;
    }
    exit();
}
if (is_file($file)) {
    throw new RuntimeException('La vista temporal ya existe.');
}
$tag = 'pos-preview-' . bin2hex(random_bytes(3));
$db->prepare('INSERT INTO users(name,username,password_hash) VALUES (?,?,?)')->execute([
    'Vista de pruebas',
    $tag,
    password_hash('admin123', PASSWORD_DEFAULT),
]);
$user = (int) $db->lastInsertId();
$db->prepare(
    "INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE name='Administrador'",
)->execute([$user]);
$db->prepare('INSERT INTO products(code,name,price,min_stock) VALUES (?,?,12.50,2)')->execute([
    $tag,
    'Producto temporal de prueba POS',
]);
$product = (int) $db->lastInsertId();
$db->prepare(
    'INSERT INTO stock_lots(product_id,lot_code,cost,quantity) VALUES (?,?,3,10)',
)->execute([$product, 'PRUEBA']);
file_put_contents(
    $file,
    json_encode(['user' => $user, 'username' => $tag, 'product' => $product], JSON_THROW_ON_ERROR),
);
echo "Vista temporal lista. Usuario: $tag\n";
