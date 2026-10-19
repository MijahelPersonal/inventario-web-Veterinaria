<?php
declare(strict_types=1);

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class View
{
    private Environment $twig;

    public function __construct(string $root, array $config)
    {
        $this->twig = new Environment(new FilesystemLoader($root . '/templates'), [
            'autoescape' => 'html',
            'debug' => false,
            'cache' => $config['environment'] === 'production' ? $root . '/storage/cache' : false,
        ]);
        $this->twig->addGlobal('app_name', $config['name']);
    }

    public function render(string $template, array $data = []): void
    {
        echo $this->twig->render($template, $data);
    }
}
