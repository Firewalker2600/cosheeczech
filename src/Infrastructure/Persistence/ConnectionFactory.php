<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

final readonly class ConnectionFactory
{
    public function __construct(private string $dsn)
    {
    }

    public function create(): \PDO
    {
        if (!str_starts_with($this->dsn, 'sqlite::memory:')) {
            $path = substr($this->dsn, \strlen('sqlite:'));
            $dir = \dirname($path);
            if ($dir !== '' && !is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        $pdo = new \PDO($this->dsn);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }
}
