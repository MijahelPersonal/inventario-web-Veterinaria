<?php
declare(strict_types=1);
namespace Core;
abstract class Controller
{
    public function __construct(protected Context $context) {}
    protected function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit();
    }
}
