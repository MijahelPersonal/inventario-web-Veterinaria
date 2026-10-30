<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
ini_set('display_errors', '0');
error_reporting(E_ALL);
try {
    if (!getenv('STRUCH_DESKTOP_DATA') || !getenv('STRUCH_DESKTOP_CONFIG')) {
        throw new DomainException(
            'Falta la carpeta de datos o la configuración de StruchVet Desktop.',
        );
    }
    $config = require dirname(__DIR__) . '/config/bootstrap.php';
    $db = Core\Database::connect($config['database']);
    $service = new App\Service\DesktopMaintenance($db, dirname(__DIR__));
    $command = $argv[1] ?? 'status';
    $result = match ($command) {
        'status' => $service->status(),
        'backup' => ['ok' => true, 'path' => $service->backup()],
        'migrate' => $service->migrate(in_array('--confirm', $argv, true)),
        'create-admin' => $service->createAdmin(
            json_decode(stream_get_contents(STDIN, 10001), true, 16, JSON_THROW_ON_ERROR),
        ),
        default => throw new DomainException('Operación de escritorio desconocida.'),
    };
    echo json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $error) {
    $code = $error instanceof PDOException ? 'database_unavailable' : 'desktop_setup_error';
    $message =
        $error instanceof PDOException
            ? 'No se pudo acceder a la base de datos. Revisa el servidor, puerto, nombre, credenciales y permisos de conexión.'
            : ($error instanceof DomainException || $error instanceof RuntimeException
                ? $error->getMessage()
                : 'No se pudo completar la operación. Revisa la configuración e inténtalo nuevamente.');
    echo json_encode(
        ['ok' => false, 'error' => $code, 'message' => $message],
        JSON_UNESCAPED_UNICODE,
    ) . PHP_EOL;
    exit(1);
}
