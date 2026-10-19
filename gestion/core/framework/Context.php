<?php
declare(strict_types=1);
namespace Core;
use App\Service\AuthService;
use PDO;
final class Context
{
    private ?PDO $connection = null;
    public function __construct(public readonly View $view, public readonly array $config) {}
    public function db(): PDO { return $this->connection ??= Database::connect($this->config['database']); }
    public function auth(): AuthService { return new AuthService($this->db()); }
}
