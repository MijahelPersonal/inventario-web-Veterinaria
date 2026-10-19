<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$tag = 'prueba-' . bin2hex(random_bytes(5));
$db->prepare('INSERT INTO users(username,name,email,password_hash) VALUES (?,?,?,?)')->execute([
    $tag,
    $tag,
    $tag . '@example.test',
    password_hash($tag, PASSWORD_DEFAULT),
]);
$userId = (int) $db->lastInsertId();
$saved = [];
$service = new App\Service\CatalogService($db);
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
    $client = $service->save('clientes', ['name' => $tag, 'active' => 1], null, $userId);
    $saved[] = ['clients', $client];
    $pet = $service->save(
        'mascotas',
        [
            'name' => $tag,
            'client_id' => $client,
            'species' => 'Canino',
            'weight' => '5.250',
            'active' => 1,
        ],
        null,
        $userId,
    );
    $saved[] = ['pets', $pet];
    $productInput = [
        'name' => $tag,
        'code' => $tag,
        'unit' => 'unidad',
        'price' => '12.50',
        'min_stock' => '2',
        'active' => 1,
    ];
    $product = $service->save('productos', $productInput, null, $userId);
    $saved[] = ['products', $product];
    $reject(fn() => $service->save('productos', $productInput, null, $userId));
    $reject(
        fn() => $service->save('productos', [...$productInput, 'price' => '-1'], null, $userId),
    );
    $reject(
        fn() => $service->save(
            'mascotas',
            ['name' => $tag, 'client_id' => '999999999', 'species' => 'Canino', 'active' => 1],
            null,
            $userId,
        ),
    );
    $service->save('clientes', ['name' => $tag], $client, $userId);
    $reject(
        fn() => $service->save(
            'mascotas',
            ['name' => $tag, 'client_id' => $client, 'species' => 'Canino', 'active' => 1],
            null,
            $userId,
        ),
    );
    $query = $db->prepare('SELECT client_id FROM pets WHERE id=?');
    $query->execute([$pet]);
    $check((int) $query->fetchColumn() === $client, 'Se perdió la relación cliente/mascota.');
    $query = $db->prepare('SELECT COUNT(*) FROM audit_events WHERE user_id=?');
    $query->execute([$userId]);
    $check((int) $query->fetchColumn() === 4, 'Auditoría incorrecta.');
    echo "OK: catálogos, decimales, duplicados, referencias y desactivación con historial.\n";
} finally {
    $db->prepare('DELETE FROM audit_events WHERE user_id=?')->execute([$userId]);
    foreach (array_reverse($saved) as [$table, $id]) {
        $db->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
    }
    $db->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
}
