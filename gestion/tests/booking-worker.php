<?php
declare(strict_types=1);
// Proceso independiente para comprobar la competencia real por un mismo horario.
putenv('STRUCH_TESTING=1');
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$input = json_decode($argv[1], true, flags: JSON_THROW_ON_ERROR);
try {
    $service = new App\Service\AppointmentService(Core\Database::connect($config['database']));
    echo $service->create($input, (int) $argv[2]);
} catch (DomainException $error) {
    echo 'rechazada';
    exit(2);
}
