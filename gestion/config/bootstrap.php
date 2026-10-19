<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require __DIR__ . '/app.php';
date_default_timezone_set($config['timezone']);
ini_set('display_errors', '0');
error_reporting(E_ALL);

return $config;
