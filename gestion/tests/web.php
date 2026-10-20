<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$service = new App\Service\WebRequestService($db);
$hash = hash('sha256', random_bytes(32));
$tag = 'web-' . bin2hex(random_bytes(5));
$key = static fn(): string => bin2hex(random_bytes(32));
$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$reject = static function (callable $operation): void {
    try {
        $operation();
    } catch (DomainException) {
        return;
    }
    throw new RuntimeException('Se aceptó una solicitud inválida');
};
$base = [
    'kind' => 'contact',
    'name' => $tag,
    'phone' => '+51 999 000 000',
    'email' => 'test@example.test',
    'message' => 'Prueba automatizada',
    'privacy' => true,
    'request_key' => $key(),
];
$db->prepare('INSERT INTO products(code,name,price,publish_online) VALUES (?,?,12.50,0)')->execute([
    $tag,
    $tag,
]);
$product = (int) $db->lastInsertId();
$db->prepare(
    'INSERT INTO stock_lots(product_id,lot_code,cost,quantity) VALUES (?,?,3,10)',
)->execute([$product, $tag]);
$lot = (int) $db->lastInsertId();
try {
    $reference = $service->submit($base, $hash);
    $check($service->submit($base, $hash) === $reference, 'Se duplicó la solicitud');
    $reject(
        fn() => $service->submit([...$base, 'request_key' => $key(), 'privacy' => false], $hash),
    );
    $reject(
        fn() => $service->submit([...$base, 'request_key' => $key(), 'email' => 'inválido'], $hash),
    );
    $appointment = [
        ...$base,
        'kind' => 'appointment',
        'request_key' => $key(),
        'pet_name' => 'Rocky',
        'species' => 'Perro',
        'service' => 'Consulta general',
        'date' => date('Y-m-d', strtotime('+2 days')),
        'time' => '10:00',
    ];
    $service->submit($appointment, $hash);
    $reject(
        fn() => $service->submit(
            [...$appointment, 'request_key' => $key(), 'date' => '2020-01-01'],
            $hash,
        ),
    );
    $order = [
        ...$base,
        'kind' => 'order',
        'request_key' => $key(),
        'items' => [['id' => $product, 'quantity' => '2', 'price' => '0.01']],
    ];
    $reject(fn() => $service->submit($order, $hash));
    $db->prepare('UPDATE products SET publish_online=1 WHERE id=?')->execute([$product]);
    $orderReference = $service->submit($order, $hash);
    $query = $db->prepare('SELECT payload FROM web_requests WHERE reference=?');
    $query->execute([$orderReference]);
    $payload = json_decode($query->fetchColumn(), true, 32, JSON_THROW_ON_ERROR);
    $check($payload['total'] === '25.00', 'Se aceptó el precio del navegador');
    $reject(
        fn() => $service->submit(
            [
                ...$order,
                'request_key' => $key(),
                'items' => [['id' => $product, 'quantity' => '11']],
            ],
            $hash,
        ),
    );
    $reject(
        fn() => $service->submit(
            [
                ...$order,
                'request_key' => $key(),
                'items' => [
                    ['id' => $product, 'quantity' => '2'],
                    ['id' => $product, 'quantity' => '2'],
                ],
            ],
            $hash,
        ),
    );
    for ($i = 0; $i < 2; $i++) {
        $service->submit([...$base, 'request_key' => $key()], $hash);
    }
    $reject(fn() => $service->submit([...$base, 'request_key' => $key()], $hash));
    $query = $db->prepare('SELECT quantity,reserved_quantity FROM stock_lots WHERE id=?');
    $query->execute([$lot]);
    $balance = $query->fetch();
    $check(
        $balance['quantity'] === '10.000' && $balance['reserved_quantity'] === '0.000',
        'Una solicitud alteró stock',
    );
    echo "OK: solicitudes web, consentimiento, datos, fechas, publicación, stock, precios del servidor, doble envío y límite de frecuencia.\n";
} finally {
    $db->prepare('DELETE FROM web_requests WHERE client_hash=?')->execute([$hash]);
    $db->prepare('DELETE FROM web_submission_limits WHERE bucket=?')->execute([$hash]);
    $db->prepare('DELETE FROM stock_lots WHERE id=?')->execute([$lot]);
    $db->prepare('DELETE FROM products WHERE id=?')->execute([$product]);
}
