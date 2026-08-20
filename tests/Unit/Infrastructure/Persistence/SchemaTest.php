<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence;

use App\Infrastructure\Persistence\Row;
use App\Infrastructure\Persistence\Schema;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    #[Test]
    public function seedsDeterministicProductIdsAcrossFreshDatabases(): void
    {
        self::assertSame($this->seedIds(), $this->seedIds());
    }

    #[Test]
    public function reseedingIsANoOp(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        Schema::create($pdo);
        Schema::seed($pdo);
        Schema::seed($pdo);

        $stmt = $pdo->query('SELECT COUNT(*) FROM products');
        assert($stmt instanceof \PDOStatement);

        self::assertSame(5, (int) $stmt->fetchColumn());
    }

    /** @return array<string, string> sku => id */
    private function seedIds(): array
    {
        $pdo = new \PDO('sqlite::memory:');
        Schema::create($pdo);
        Schema::seed($pdo);

        $stmt = $pdo->query('SELECT sku, id FROM products ORDER BY sku');
        assert($stmt instanceof \PDOStatement);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        $ids = [];
        foreach ($rows as $row) {
            $ids[Row::string($row, 'sku')] = Row::string($row, 'id');
        }

        return $ids;
    }
}
