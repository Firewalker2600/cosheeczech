<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\Sku;
use App\Domain\Repository\ProductRepositoryInterface;

final readonly class SqliteProductRepository implements ProductRepositoryInterface
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function find(Sku $sku): ?Product
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE sku = :sku');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute(['sku' => $sku->value]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new Product(
            id: Row::string($row, 'id'),
            sku: new Sku(Row::string($row, 'sku')),
            name: Row::string($row, 'name'),
            price: new Money(Row::int($row, 'price_minor')),
            description: Row::nullableString($row, 'description'),
        );
    }

    public function save(Product $product): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (id, sku, name, price_minor, description)
             VALUES (:id, :sku, :name, :price_minor, :description)
             ON CONFLICT(sku) DO UPDATE SET
                 name = excluded.name,
                 price_minor = excluded.price_minor,
                 description = excluded.description',
        );
        assert($stmt instanceof \PDOStatement);

        $stmt->execute([
            'id' => $product->id,
            'sku' => $product->sku->value,
            'name' => $product->name,
            'price_minor' => $product->price->minor,
            'description' => $product->description,
        ]);
    }
}
