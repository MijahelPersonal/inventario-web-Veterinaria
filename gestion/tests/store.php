<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$tag = 'tienda-' . bin2hex(random_bytes(5));
$db->beginTransaction();
try {
    $db->prepare('INSERT INTO product_categories(name) VALUES (?)')->execute([$tag]);
    $category = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO brands(name) VALUES (?)')->execute([$tag]);
    $brand = (int) $db->lastInsertId();
    $db->prepare(
        'INSERT INTO products(code,name,price,category_id,brand_id) VALUES (?,?,12.50,?,?)',
    )->execute([$tag, $tag, $category, $brand]);
    $product = (int) $db->lastInsertId();
    $query = $db->prepare(
        'INSERT INTO stock_lots(product_id,lot_code,expires_at,cost,quantity,reserved_quantity) VALUES (?,?,?,3,?,?)',
    );
    $query->execute([$product, 'VIGENTE', date('Y-m-d', strtotime('+30 days')), '10', '2']);
    $query->execute([$product, 'VENCIDO', date('Y-m-d', strtotime('-1 day')), '50', '0']);
    $query->execute([$product, 'SIN-FECHA', null, '3', '0']);
    $store = new App\Model\Store($db);
    $result = $store->products($tag, $category, 1);
    if (
        $result['total'] !== 1 ||
        count($result['products']) !== 1 ||
        $result['products'][0]['available'] !== '11.000'
    ) {
        throw new RuntimeException('Disponibilidad o agrupación incorrecta');
    }
    if ($store->products($tag, $category, 2)['products'] !== []) {
        throw new RuntimeException('Paginación incorrecta');
    }
    $db->prepare('UPDATE products SET active=0 WHERE id=?')->execute([$product]);
    if ($store->products($tag, null, 1)['total'] !== 0) {
        throw new RuntimeException('Se publicó un producto inactivo');
    }
    echo "OK: filtros, paginación, lotes vencidos, reservas y productos inactivos del POS.\n";
} finally {
    $db->rollBack();
}
