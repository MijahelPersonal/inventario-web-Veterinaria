<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$tag = 'reporte-' . bin2hex(random_bytes(5));
$db->beginTransaction();
try {
    $db->prepare('INSERT INTO users(name,username,password_hash) VALUES (?,?,?)')->execute([
        $tag,
        $tag,
        password_hash($tag, PASSWORD_DEFAULT),
    ]);
    $user = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO clients(name) VALUES (?)')->execute([$tag]);
    $client = (int) $db->lastInsertId();
    $db->prepare("INSERT INTO pets(client_id,name,species) VALUES (?,?,'Canino')")->execute([
        $client,
        $tag,
    ]);
    $pet = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO veterinarians(name) VALUES (?)')->execute([$tag]);
    $vet = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO services(name,price) VALUES (?,30)')->execute([$tag]);
    $service = (int) $db->lastInsertId();
    $db->prepare(
        "INSERT INTO appointments(pet_id,veterinarian_id,service_id,start_at,end_at,price,status,user_id,request_key) VALUES (?,?,?,CURRENT_DATE+INTERVAL 10 HOUR,CURRENT_DATE+INTERVAL 11 HOUR,30,'atendida',?,?)",
    )->execute([$pet, $vet, $service, $user, bin2hex(random_bytes(32))]);
    $appointment = (int) $db->lastInsertId();
    $model = new App\Model\Finance($db);
    $session = $model->open($user, '0.00');
    $sale = $model->createSale($client, $appointment, $user, bin2hex(random_bytes(32)));
    $model->totals($sale, '30.00', '0.00');
    $model->addPayment(
        $sale,
        $session,
        '10',
        'efectivo',
        null,
        'Prueba',
        $user,
        bin2hex(random_bytes(32)),
    );
    $model->addPayment(
        $sale,
        $session,
        '15',
        'tarjeta',
        null,
        'Prueba',
        $user,
        bin2hex(random_bytes(32)),
    );
    $report = new App\Service\ReportService($db);
    $result = $report->generate([
        'type' => 'citas',
        'start' => date('Y-m-d'),
        'end' => date('Y-m-d'),
        'vet' => $vet,
    ]);
    if (
        count($result['rows']) !== 1 ||
        $result['metrics']['collected'] !== '25.00' ||
        $result['metrics']['outstanding'] !== '5.00' ||
        $result['metrics']['attended'] !== 1
    ) {
        throw new RuntimeException('El reporte duplicó importes o perdió el filtro');
    }
    if (
        !str_starts_with(App\Service\ReportService::csvCell('=1+1'), "'") ||
        App\Service\ReportService::csvCell('-10.00') !== '-10.00'
    ) {
        throw new RuntimeException('CSV inseguro o número alterado');
    }
    foreach (
        [
            ['start' => '2026-02-30'],
            ['type' => 'desconocido'],
            ['start' => '2020-01-01', 'end' => '2026-01-01'],
        ]
        as $invalid
    ) {
        try {
            $report->generate($invalid);
            throw new RuntimeException('Se aceptó filtro inválido');
        } catch (DomainException) {
        }
    }
    foreach (array_keys(App\Service\ReportService::TYPES) as $type) {
        $report->generate(['type' => $type]);
    }
    $dashboard = (new App\Model\Report($db))->dashboard(false);
    if (isset($dashboard['payments_today'])) {
        throw new RuntimeException('El dashboard sin acceso incluyó finanzas');
    }
    echo "OK: filtros, periodo, agregados sin duplicación, saldo, reportes y CSV seguro.\n";
} finally {
    $db->rollBack();
}
