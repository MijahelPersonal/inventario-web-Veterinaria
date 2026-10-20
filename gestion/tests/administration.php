<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$tag = 'admin-' . bin2hex(random_bytes(5));
$db->prepare('INSERT INTO users(name,username,password_hash) VALUES (?,?,?)')->execute([
    $tag,
    $tag,
    password_hash($tag, PASSWORD_DEFAULT),
]);
$actor = (int) $db->lastInsertId();
$model = new App\Model\Administration($db);
$settings = $model->settings();
$roles = $model->roles();
$admin = (int) array_values(array_filter($roles, fn($r) => $r['name'] === 'Administrador'))[0][
    'id'
];
$cashier = (int) array_values(array_filter($roles, fn($r) => $r['name'] === 'Cajero'))[0]['id'];
$db->prepare('INSERT INTO user_roles(user_id,role_id) VALUES (?,?)')->execute([$actor, $admin]);
$service = new App\Service\AdministrationService($db);
$created = null;
$reject = static function (callable $operation): void {
    try {
        $operation();
    } catch (DomainException) {
        return;
    }
    throw new RuntimeException('Operación inválida aceptada');
};
try {
    $input = ['name' => $tag, 'username' => $tag, 'roles' => [$admin], 'active' => 1];
    if (
        count($roles) !== 2 ||
        array_diff(array_column($roles, 'name'), ['Administrador', 'Cajero'])
    ) {
        throw new RuntimeException('Roles inesperados');
    }
    $reject(fn() => $service->user([...$input, 'roles' => [$admin, $cashier]], $actor, $actor));
    $reject(fn() => $service->user([...$input, 'roles' => [$cashier]], $actor, $actor));
    $reject(
        fn() => $service->user(
            ['name' => $tag, 'username' => $tag, 'roles' => [$admin]],
            $actor,
            $actor,
        ),
    );
    $created = $service->user(
        [
            'name' => $tag,
            'username' => $tag . '-nuevo',
            'roles' => [$cashier],
            'active' => 1,
            'password' => 'prueba-temporal-123',
        ],
        null,
        $actor,
    );
    $reject(
        fn() => $service->user(
            [
                'name' => $tag,
                'username' => $tag . '-nuevo',
                'roles' => [$cashier],
                'active' => 1,
                'password' => 'prueba-temporal-123',
            ],
            null,
            $actor,
        ),
    );
    $reject(
        fn() => $service->settings(
            [...$settings, 'primary_color' => 'javascript:alert(1)'],
            $actor,
        ),
    );
    $service->settings([...$settings, 'theme' => 'dark'], $actor);
    if ($model->settings()['theme'] !== 'dark') {
        throw new RuntimeException('Configuración no guardada');
    }
    echo "OK: roles, último administrador, cuenta propia, duplicados y configuración validada.\n";
} finally {
    foreach ($settings as $key => $value) {
        $model->setSetting($key, $value);
    }
    $db->prepare('DELETE FROM audit_events WHERE user_id=?')->execute([$actor]);
    foreach (array_filter([$created, $actor]) as $id) {
        $db->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$id]);
        $db->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
    }
}
