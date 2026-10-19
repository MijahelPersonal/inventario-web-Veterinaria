<?php
// Router exclusivo para el servidor de desarrollo PHP.
$public = realpath(dirname(__DIR__) . '/public');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$file = realpath($public . $path);
if ($file !== false && str_starts_with($file, $public . DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}
require $public . '/index.php';
