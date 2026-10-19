<?php
declare(strict_types=1);

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class View
{
    private Environment $twig;
    private array $permissions = [];

    public function __construct(string $root, array $config)
    {
        $this->twig = new Environment(new FilesystemLoader($root . '/templates'), [
            'autoescape' => 'html',
            'debug' => false,
            'cache' => $config['environment'] === 'production' ? $root . '/storage/cache' : false,
        ]);
        $this->twig->addGlobal('app_name', $config['name']);
        $this->twig->addFunction(
            new \Twig\TwigFunction(
                'can',
                fn(string $permission): bool => in_array('*', $this->permissions, true) ||
                    in_array($permission, $this->permissions, true),
            ),
        );
        $this->twig->addGlobal('catalogs', \App\Service\CatalogService::definitions());
    }

    public function render(string $template, array $data = []): void
    {
        $this->twig->addGlobal('csrf', $_SESSION['csrf'] ?? '');
        $this->twig->addGlobal('flash', Session::consumeFlash());
        echo $this->twig->render($template, $data);
    }

    // La vista oculta acciones no permitidas; el servidor vuelve a verificar cada operación.
    public function setUser(?array $user): void
    {
        $this->permissions = $user['permissions'] ?? [];
        $this->twig->addGlobal('current_user', $user);
    }
}
