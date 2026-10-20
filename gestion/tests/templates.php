<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
$root = dirname(__DIR__);
$twig = new Twig\Environment(new Twig\Loader\FilesystemLoader($root . '/templates'));
$twig->addFunction(new Twig\TwigFunction('can', static fn(string $permission): bool => true));
$twig->addFunction(new Twig\TwigFunction('icon', static fn(string $name): string => ''));
$count = 0;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/templates', FilesystemIterator::SKIP_DOTS),
);
foreach ($files as $file) {
    if (!str_ends_with($file->getFilename(), '.twig')) {
        continue;
    }
    $name = str_replace('\\', '/', substr($file->getPathname(), strlen($root . '/templates/')));
    $twig->load($name);
    $count++;
}
FastRoute\simpleDispatcher(require $root . '/core/app/routes.php');
echo "OK: $count plantillas Twig compiladas y rutas sin conflictos.\n";
