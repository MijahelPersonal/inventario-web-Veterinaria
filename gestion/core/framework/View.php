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
        $this->twig->addGlobal('csrf', $_SESSION['csrf'] ?? '');
        $this->twig->addGlobal('flash', Session::consumeFlash());
        echo $this->twig->render($template, $data);
    }

    public function setUser(?array $user): void { $this->twig->addGlobal('current_user', $user); }
}
