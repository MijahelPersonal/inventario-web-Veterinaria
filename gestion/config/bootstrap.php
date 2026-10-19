<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require __DIR__ . '/app.php';
if (in_array(PHP_SAPI, ['cli', 'cli-server'], true) && getenv('STRUCH_TESTING') === '1') {
    $config = array_replace_recursive($config, require __DIR__ . '/local.test.php');
    if (!str_ends_with($config['database']['name'], '_test')) {
        throw new RuntimeException('Las pruebas requieren una base terminada en _test.');
    }
}
date_default_timezone_set($config['timezone']);
ini_set('display_errors', '0');
error_reporting(E_ALL);

return $config;
