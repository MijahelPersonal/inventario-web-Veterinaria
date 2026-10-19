<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$tag = 'stock-' . bin2hex(random_bytes(5));
$db->prepare('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)')->execute([
    $tag,
    $tag . '@example.test',
    password_hash($tag, PASSWORD_DEFAULT),
]);
$user = (int) $db->lastInsertId();
$db->prepare('INSERT INTO products(code,name,price) VALUES (?,?,10)')->execute([$tag, $tag]);
$product = (int) $db->lastInsertId();
$stock = new App\Service\InventoryService($db);
$input = [
    'kind' => 'inicial',
    'product_id' => $product,
    'quantity' => '2.500',
    'cost' => '3.20',
    'lot_code' => $tag,
    'reason' => 'Prueba',
    'request_key' => bin2hex(random_bytes(32)),
];
$reject = static function (callable $operation): void {
    try {
        $operation();
    } catch (DomainException) {
        return;
    }
    throw new RuntimeException('Se aceptó una salida inválida.');
};
try {
    $first = $stock->movement($input, $user);
    if ($stock->movement($input, $user) !== $first) {
        throw new RuntimeException('Se duplicó una entrada');
    }
    $query = $db->prepare('SELECT * FROM stock_lots WHERE product_id=?');
    $query->execute([$product]);
    $lot = $query->fetch();
    $output = [
        'product_id' => $product,
        'lot_id' => $lot['id'],
        'kind' => 'consumo',
        'quantity' => '0.250',
        'reason' => 'Atención de prueba',
        'request_key' => bin2hex(random_bytes(32)),
    ];
    $stock->movement($output, $user);
    $reject(
        fn() => $stock->movement(
            [...$output, 'quantity' => '9', 'request_key' => bin2hex(random_bytes(32))],
            $user,
        ),
    );
    $db->beginTransaction();
    $cost = $stock->takeForSale($product, '1.000', $user, 1);
    if ($cost !== '3.20') {
        throw new RuntimeException('Costo incorrecto');
    }
    $db->rollBack();
    $query->execute([$product]);
    $lot = $query->fetch();
    if ($lot['quantity'] !== '2.250') {
        throw new RuntimeException('La transacción no revirtió el stock');
    }
    $db->prepare('UPDATE stock_lots SET expires_at=? WHERE id=?')->execute([
        date('Y-m-d', strtotime('-1 day')),
        $lot['id'],
    ]);
    $reject(
        fn() => $stock->movement([...$output, 'request_key' => bin2hex(random_bytes(32))], $user),
    );
    echo "OK: entradas, fracciones, doble envío, stock insuficiente, costos, rollback y vencimientos.\n";
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->prepare('DELETE FROM audit_events WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM stock_movements WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM stock_lots WHERE product_id=?')->execute([$product]);
    $db->prepare('DELETE FROM products WHERE id=?')->execute([$product]);
    $db->prepare('DELETE FROM users WHERE id=?')->execute([$user]);
}
