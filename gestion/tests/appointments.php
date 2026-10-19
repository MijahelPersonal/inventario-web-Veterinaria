<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$tag = 'agenda-' . bin2hex(random_bytes(5));
$db->prepare('INSERT INTO users(username,name,email,password_hash) VALUES (?,?,?,?)')->execute([
    $tag,
    $tag,
    $tag . '@example.test',
    password_hash($tag, PASSWORD_DEFAULT),
]);
$user = (int) $db->lastInsertId();
$db->prepare('INSERT INTO clients(name) VALUES (?)')->execute([$tag]);
$client = (int) $db->lastInsertId();
$db->prepare('INSERT INTO pets(client_id,name,species) VALUES (?,?,?)')->execute([
    $client,
    $tag,
    'Canino',
]);
$pet = (int) $db->lastInsertId();
$db->prepare('INSERT INTO veterinarians(name) VALUES (?)')->execute([$tag]);
$vet = (int) $db->lastInsertId();
$db->prepare('INSERT INTO services(name,price,duration_minutes) VALUES (?,50,30)')->execute([$tag]);
$service = (int) $db->lastInsertId();
$agenda = new App\Service\AppointmentService($db);
$input = [
    'pet_id' => $pet,
    'veterinarian_id' => $vet,
    'service_id' => $service,
    'start_at' => date('Y-m-d', strtotime('+2 days')) . 'T10:00',
    'request_key' => bin2hex(random_bytes(32)),
];
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
    throw new RuntimeException('Se aceptó una cita inválida.');
};
try {
    $first = $agenda->create($input, $user);
    $check($agenda->create($input, $user) === $first, 'Doble envío duplicó cita');
    $reject(
        fn() => $agenda->create([...$input, 'request_key' => bin2hex(random_bytes(32))], $user),
    );
    $adjacent = $agenda->create(
        [
            ...$input,
            'start_at' => substr($input['start_at'], 0, 11) . '10:30',
            'request_key' => bin2hex(random_bytes(32)),
        ],
        $user,
    );
    $agenda->changeStatus($first, 'confirmada', '', $user);
    $reject(fn() => $agenda->changeStatus($first, 'atendida', '', $user));
    $agenda->changeStatus($first, 'cancelada', '', $user);
    $reject(fn() => $agenda->changeStatus($first, 'confirmada', '', $user));
    $replacement = $agenda->create([...$input, 'request_key' => bin2hex(random_bytes(32))], $user);
    $agenda->saveHours(
        [
            'veterinarian_id' => $vet,
            'weekday' => date('N', strtotime('+2 days')),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ],
        $user,
    );
    $reject(
        fn() => $agenda->create(
            [
                ...$input,
                'start_at' => substr($input['start_at'], 0, 11) . '07:00',
                'request_key' => bin2hex(random_bytes(32)),
            ],
            $user,
        ),
    );
    // Dos procesos independientes compiten por la misma hora libre.
    $workers = [];
    for ($i = 0; $i < 2; $i++) {
        $booking = [
            ...$input,
            'start_at' => substr($input['start_at'], 0, 11) . '14:00',
            'request_key' => bin2hex(random_bytes(32)),
        ];
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/booking-worker.php', json_encode($booking), (string) $user],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        if (!is_resource($process)) {
            throw new RuntimeException('No se pudo crear el proceso de prueba');
        }
        fclose($pipes[0]);
        $workers[] = [$process, $pipes];
    }
    $codes = [];
    foreach ($workers as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $codes[] = proc_close($process);
        if ($error !== '') {
            throw new RuntimeException($error);
        }
    }
    sort($codes);
    $check($codes === [0, 2], 'Dos citas compitieron sin exclusión correcta');
    echo "OK: agenda, doble envío, horarios, cancelación, límites y concurrencia real.\n";
} finally {
    $db->prepare('DELETE FROM audit_events WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM appointments WHERE user_id=?')->execute([$user]);
    $db->prepare('DELETE FROM veterinarian_hours WHERE veterinarian_id=?')->execute([$vet]);
    $db->prepare('DELETE FROM services WHERE id=?')->execute([$service]);
    $db->prepare('DELETE FROM veterinarians WHERE id=?')->execute([$vet]);
    $db->prepare('DELETE FROM pets WHERE id=?')->execute([$pet]);
    $db->prepare('DELETE FROM clients WHERE id=?')->execute([$client]);
    $db->prepare('DELETE FROM users WHERE id=?')->execute([$user]);
}
