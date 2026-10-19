<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$tag = 'caja-' . bin2hex(random_bytes(5));
$db->prepare('INSERT INTO users(name,username,password_hash) VALUES (?,?,?)')->execute([
    $tag,
    $tag,
    password_hash($tag, PASSWORD_DEFAULT),
]);
$user = (int) $db->lastInsertId();
$db->prepare('INSERT INTO products(code,name,price) VALUES (?,?,10.50)')->execute([$tag, $tag]);
$product = (int) $db->lastInsertId();
$stock = new App\Service\InventoryService($db);
$finance = new App\Service\FinanceService($db);
$model = new App\Model\Finance($db);
$key = static fn() => bin2hex(random_bytes(32));
$check = static function (bool $valid, string $message): void {
    if (!$valid) {
        throw new RuntimeException($message);
    }
};
$reject = static function (callable $operation): void {
    try {
        $operation();
    } catch (DomainException) {
        return;
    }
    throw new RuntimeException('Se aceptó una operación inválida.');
};
try {
    $stock->movement(
        [
            'kind' => 'inicial',
            'product_id' => $product,
            'quantity' => '2',
            'cost' => '3',
            'lot_code' => $tag,
            'reason' => 'Prueba',
            'request_key' => $key(),
        ],
        $user,
    );
    $input = [
        'items' => [['item' => 'product:' . $product, 'quantity' => '1']],
        'request_key' => $key(),
    ];
    $sale = $finance->sale($input, $user);
    $check($finance->sale($input, $user) === $sale, 'Doble envío de venta');
    $check($model->sale($sale)['total'] === '10.50', 'Total incorrecto');
    $reject(
        fn() => $finance->sale(
            [
                'items' => [['item' => 'product:' . $product, 'quantity' => '9']],
                'request_key' => $key(),
            ],
            $user,
        ),
    );
    $reject(
        fn() => $finance->payment(
            ['sale_id' => $sale, 'amount' => '1', 'method' => 'efectivo', 'request_key' => $key()],
            $user,
        ),
    );
    $finance->cash('open', ['amount' => '100'], $user);
    $reject(fn() => $finance->cash('open', ['amount' => '0'], $user));
    $payment = [
        'sale_id' => $sale,
        'amount' => '4.50',
        'method' => 'efectivo',
        'request_key' => $key(),
    ];
    $paid = $finance->payment($payment, $user);
    $check($finance->payment($payment, $user) === $paid, 'Cobro duplicado');
    $card = $finance->payment(
        ['sale_id' => $sale, 'amount' => '6', 'method' => 'tarjeta', 'request_key' => $key()],
        $user,
    );
    $check($model->paid($sale) === '10.50', 'Saldo incorrecto');
    $reject(
        fn() => $finance->payment(
            [
                'sale_id' => $sale,
                'amount' => '0.01',
                'method' => 'efectivo',
                'request_key' => $key(),
            ],
            $user,
        ),
    );
    $check($model->expected($model->session($user)) === '104.50', 'Tarjeta alteró efectivo');
    $finance->reverse($paid, 'Corrección de prueba', $user);
    $reject(fn() => $finance->reverse($paid, 'Doble reversión', $user));
    $check($model->paid($sale) === '6.00', 'Reversión incorrecta');
    $finance->cash(
        'entry',
        ['amount' => '10', 'direction' => 'out', 'reason' => 'Retiro', 'request_key' => $key()],
        $user,
    );
    $check($model->expected($model->session($user)) === '90.00', 'Egreso incorrecto');
    $finance->cash('close', ['amount' => '89'], $user);
    $check($model->sessions($user)[0]['difference'] === '-1.00', 'Diferencia incorrecta');
    $reject(
        fn() => $finance->payment(
            ['sale_id' => $sale, 'amount' => '1', 'method' => 'efectivo', 'request_key' => $key()],
            $user,
        ),
    );
    $reject(fn() => $finance->voidSale($sale, 'Debe reembolsar primero', $user));
    $finance->cash('open', ['amount' => '0'], $user);
    $finance->reverse($card, 'Reembolso total', $user);
    $finance->voidSale($sale, 'Anulación de prueba', $user);
    $reject(fn() => $finance->voidSale($sale, 'No repetir devolución', $user));
    $query = $db->prepare('SELECT SUM(quantity) FROM stock_lots WHERE product_id=?');
    $query->execute([$product]);
    $check($query->fetchColumn() === '2.000', 'No se devolvió el stock');
    $pdf = (new App\Service\DocumentService())->bytes('<h1>Comprobante de prueba</h1>');
    $check(str_starts_with($pdf, '%PDF-'), 'PDF inválido');
    echo "OK: ventas, stock, rollback, cobros parciales, doble envío, sobrecobro, reversión, cierre y PDF.\n";
} finally {
    $db->prepare('DELETE FROM audit_events WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM payments WHERE user_id=? AND reversal_of IS NOT NULL')->execute([
        $user,
    ]);
    $db->prepare('DELETE FROM payments WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM cash_entries WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM cash_sessions WHERE user_id=?')->execute([$user]);
    $db->prepare(
        'DELETE si FROM sale_items si JOIN sales s ON s.id=si.sale_id WHERE s.user_id=?',
    )->execute([$user]);
    $db->prepare('DELETE FROM sales WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM stock_movements WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM stock_lots WHERE product_id=?')->execute([$product]);
    $db->prepare('DELETE FROM products WHERE id=?')->execute([$product]);
    $db->prepare('DELETE FROM users WHERE id=?')->execute([$user]);
}
