<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');
require dirname(__DIR__) . '/scripts/migrate.php';
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$root = dirname(__DIR__);
$tag = 'desktop-' . bin2hex(random_bytes(6));
$directory = sys_get_temp_dir() . '/' . $tag;
mkdir($directory, 0700);
$configFile = $directory . '/config.json';
file_put_contents(
    $configFile,
    json_encode(['database' => $config['database']], JSON_THROW_ON_ERROR),
);
chmod($configFile, 0600);
putenv('STRUCH_DESKTOP_DATA=' . $directory);
putenv('STRUCH_DESKTOP_CONFIG=' . $configFile);
Core\Paths::prepare();
$check = static function (bool $ok, string $message): void {
    if (!$ok) {
        throw new RuntimeException($message);
    }
};
$reject = static function (callable $operation): void {
    try {
        $operation();
    } catch (DomainException) {
        return;
    }
    throw new RuntimeException('Operación no confirmada aceptada.');
};
$service = new App\Service\DesktopMaintenance($db, $root);
$server = null;
$brand = null;
$futureMigration = $tag . '-future.sql';
try {
    $desktopConfig = require $root . '/config/bootstrap.php';
    $check(
        $desktopConfig['database']['name'] === $config['database']['name'],
        'Cambió la base de pruebas.',
    );
    $check(
        Core\Paths::storage('uploads/products') === $directory . '/storage/uploads/products',
        'Subidas no aisladas.',
    );
    try {
        Core\Paths::storage('../config/local.php');
        throw new RuntimeException('Aceptó traversal.');
    } catch (InvalidArgumentException) {
    }
    $check($service->status()['pending'] === [], 'Migraciones de prueba pendientes.');
    $reject(fn() => $service->migrate(false));
    $check($service->migrate(true)['applied'] === [], 'Migración idempotente fallida.');
    $journal = Core\Paths::storage('private/migration-attempt.json');
    file_put_contents($journal, '{"migration":"interrumpida.sql"}');
    $check($service->status()['recovery_required'], 'No detectó una migración interrumpida.');
    $reject(fn() => $service->migrate(true));
    unlink($journal);
    $db->prepare('INSERT INTO schema_migrations(name) VALUES (?)')->execute([$futureMigration]);
    $check(!$service->status()['compatible'], 'Una versión vieja aceptó el esquema nuevo.');
    $reject(fn() => $service->migrate(true));
    $db->prepare('DELETE FROM schema_migrations WHERE name=?')->execute([$futureMigration]);
    $db->prepare('INSERT INTO brands(name) VALUES (?)')->execute([$tag . " ' ñ"]);
    $brand = (int) $db->lastInsertId();
    $backup = $service->backup();
    $sql = file_get_contents($backup);
    $check(
        str_starts_with($backup, $directory . '/backups/'),
        'Respaldo fuera de la carpeta privada.',
    );
    $check(
        str_contains($sql, 'CREATE TABLE `brands`') &&
            str_contains($sql, $db->quote($tag . " ' ñ")),
        'El respaldo no conserva el esquema o los valores escapados.',
    );
    $check(
        !str_ends_with($backup, '.partial') && !glob($directory . '/backups/*.partial'),
        'Respaldo parcial publicado.',
    );
    $check(
        $db->query('SELECT COUNT(*) FROM brands WHERE id=' . $brand)->fetchColumn() == 1,
        'El respaldo modificó datos.',
    );
    if ((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
        $reject(
            fn() => $service->createAdmin([
                'name' => 'Prueba',
                'username' => 'admin-test',
                'password' => 'desktop-test-12345',
            ]),
        );
    }
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    $token = bin2hex(random_bytes(32));
    $env = getenv();
    $env['STRUCH_DESKTOP_TOKEN'] = $token;
    $server = proc_open(
        [
            PHP_BINARY,
            '-S',
            '127.0.0.1:' . $port,
            '-t',
            $root . '/public',
            $root . '/scripts/desktop-router.php',
        ],
        [
            ['pipe', 'r'],
            ['file', $directory . '/server.log', 'a'],
            ['file', $directory . '/server.log', 'a'],
        ],
        $pipes,
        $root,
        $env,
    );
    $request = static function (string $path, ?string $key, ?string $host = null) use (
        $port,
    ): array {
        $headers =
            ($key !== null ? 'X-Struch-Desktop-Token: ' . $key . "\r\n" : '') .
            ($host ? 'Host: ' . $host . "\r\n" : '');
        $body = @file_get_contents(
            'http://127.0.0.1:' . $port . $path,
            false,
            stream_context_create([
                'http' => ['header' => $headers, 'ignore_errors' => true, 'timeout' => 3],
            ]),
        );
        return [$http_response_header[0] ?? '', $body];
    };
    $ready = false;
    for ($i = 0; $i < 40; $i++) {
        [$status, $body] = $request('/__desktop/health', $token);
        if (str_contains($status, '200')) {
            $ready = true;
            break;
        }
        usleep(50000);
    }
    $check($ready && json_decode($body, true)['ok'], 'No inició el servicio privado.');
    $check(
        str_contains($request('/__desktop/health', null)[0], '403'),
        'Health expuesto sin token.',
    );
    $check(
        str_contains($request('/login', 'incorrecta')[0], '403'),
        'Página expuesta sin token válido.',
    );
    $check(
        str_contains($request('/__desktop/health', $token, 'localhost:' . $port)[0], '403'),
        'Host alterado aceptado.',
    );
    $check(
        str_contains($request('/assets/icons/heart-pulse.svg', null)[0], '403'),
        'Recursos expuestos sin token.',
    );
    [$status, $body] = $request('/assets/icons/heart-pulse.svg', $token);
    $check(
        str_contains($status, '200') && str_contains($body, '<svg'),
        'Recursos autorizados no disponibles.',
    );
    [$status, $body] = $request('/login', $token);
    $check(
        str_contains($status, '200') && str_contains($body, 'csrf'),
        'Login PHP no renderizado.',
    );
    $check(
        count(glob(Core\Paths::storage('sessions') . '/*')) > 0,
        'Sesiones guardadas fuera del perfil privado.',
    );
    $check(count(glob(Core\Paths::storage('cache') . '/*')) > 0, 'Caché Twig no aislado.');
    echo "OK: configuración y almacenamiento externos, respaldo SQL consistente sin mutaciones, migraciones explícitas e idempotentes, bloqueo de recuperación, control Host/token de páginas/recursos/health, login PHP y sesiones privadas.\n";
} finally {
    if (is_resource($server)) {
        proc_terminate($server);
        proc_close($server);
    }
    $db->prepare('DELETE FROM schema_migrations WHERE name=?')->execute([$futureMigration]);
    if ($brand) {
        $db->prepare('DELETE FROM brands WHERE id=?')->execute([$brand]);
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($directory);
    putenv('STRUCH_DESKTOP_DATA');
    putenv('STRUCH_DESKTOP_CONFIG');
}
