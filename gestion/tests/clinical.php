<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$tag = 'ficha-' . bin2hex(random_bytes(5));
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
$db->prepare('INSERT INTO services(name,price,duration_minutes) VALUES (?,30,30)')->execute([$tag]);
$service = (int) $db->lastInsertId();
$agenda = new App\Service\AppointmentService($db);
$clinic = new App\Service\ClinicalService($db);
$input = [
    'pet_id' => $pet,
    'veterinarian_id' => $vet,
    'service_id' => $service,
    'start_at' => date('Y-m-d', strtotime('+3 days')) . 'T10:00',
    'request_key' => bin2hex(random_bytes(32)),
];
$reject = static function (callable $operation): void {
    try {
        $operation();
    } catch (DomainException) {
        return;
    }
    throw new RuntimeException('Operación inválida aceptada');
};
try {
    $id = $agenda->create($input, $user);
    $clinic->notes(
        $id,
        [
            'symptoms' => 'Prueba de síntomas',
            'diagnosis' => 'Observación',
            'treatment' => 'Seguimiento',
        ],
        $user,
    );
    $clinic->reschedule(
        $id,
        ['veterinarian_id' => $vet, 'start_at' => substr($input['start_at'], 0, 11) . '11:00'],
        $user,
    );
    $record = (new App\Model\ClinicalRecord($db))->appointment($id);
    if (
        $record['symptoms'] !== 'Prueba de síntomas' ||
        substr($record['start_at'], 11, 5) !== '11:00' ||
        substr($record['end_at'], 11, 5) !== '11:30' ||
        $record['price'] !== '30.00'
    ) {
        throw new RuntimeException('Notas o reprogramación incorrecta');
    }
    $other = $agenda->create(
        [
            ...$input,
            'start_at' => substr($input['start_at'], 0, 11) . '12:00',
            'request_key' => bin2hex(random_bytes(32)),
        ],
        $user,
    );
    $reject(
        fn() => $clinic->reschedule(
            $id,
            ['veterinarian_id' => $vet, 'start_at' => substr($input['start_at'], 0, 11) . '12:15'],
            $user,
        ),
    );
    $agenda->changeStatus($id, 'confirmada', '', $user);
    $sale = (new App\Service\FinanceService($db))->sale(
        ['appointment_id' => $id, 'request_key' => bin2hex(random_bytes(32))],
        $user,
    );
    $reject(
        fn() => $clinic->reschedule(
            $id,
            ['veterinarian_id' => $vet, 'start_at' => substr($input['start_at'], 0, 11) . '14:00'],
            $user,
        ),
    );
    echo "OK: fichas, notas, duración y precio históricos, cruces y reprogramación con cargo bloqueada.\n";
} finally {
    $db->prepare('DELETE FROM audit_events WHERE user_id=?')->execute([$user]);
    $db->prepare(
        'DELETE si FROM sale_items si JOIN sales s ON s.id=si.sale_id WHERE s.user_id=?',
    )->execute([$user]);
    $db->prepare('DELETE FROM sales WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM appointments WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM pets WHERE id=?')->execute([$pet]);
    $db->prepare('DELETE FROM clients WHERE id=?')->execute([$client]);
    $db->prepare('DELETE FROM veterinarians WHERE id=?')->execute([$vet]);
    $db->prepare('DELETE FROM services WHERE id=?')->execute([$service]);
    $db->prepare('DELETE FROM users WHERE id=?')->execute([$user]);
}
