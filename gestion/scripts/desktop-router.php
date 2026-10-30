<?php
declare(strict_types=1);
// Solo Electron conoce la clave efímera. También se comprueba Host para impedir DNS rebinding.
$token = (string) getenv('STRUCH_DESKTOP_TOKEN');
$provided = (string) ($_SERVER['HTTP_X_STRUCH_DESKTOP_TOKEN'] ?? '');
$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
$port = (int) ($_SERVER['SERVER_PORT'] ?? 0);
if (
    PHP_SAPI !== 'cli-server' ||
    !preg_match('/^[a-f0-9]{64}$/D', $token) ||
    !hash_equals($token, $provided) ||
    $host !== '127.0.0.1:' . $port ||
    !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Servicio privado de StruchVet Desktop.';
    return true;
}
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if ($path === '/__desktop/health' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo '{"ok":true}';
    return true;
}
$public = realpath(dirname(__DIR__) . '/public');
$file = realpath($public . $path);
// Todos los recursos pasan por esta autorización antes de servirse.
if (
    $file !== false &&
    str_starts_with($file, $public . DIRECTORY_SEPARATOR) &&
    is_file($file) &&
    strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php'
) {
    return false;
}
require $public . '/index.php';
return true;
