<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Uuid;

/**
 * SQLite schema (DDL) and idempotent demo-catalog seed.
 */
final class Schema
{
    /**
     * Fixed namespace for name-based (UUIDv5) product ids.
     * Arbitrary but MUST stay stable — changing it changes every persisted id.
     */
    private const PRODUCT_NAMESPACE = 'a1b2c3d4-e5f6-4789-8abc-def012345678';

    public static function create(\PDO $pdo): void
    {
        $pdo->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS products (
                id           TEXT PRIMARY KEY,
                sku          TEXT NOT NULL UNIQUE,
                name         TEXT NOT NULL,
                price_minor  INTEGER NOT NULL,
                description  TEXT NULL
            );

            CREATE TABLE IF NOT EXISTS carts (
                id TEXT PRIMARY KEY
            );

            CREATE TABLE IF NOT EXISTS cart_items (
                cart_id    TEXT NOT NULL,
                product_id TEXT NOT NULL,
                quantity   INTEGER NOT NULL,
                PRIMARY KEY (cart_id, product_id),
                FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS orders (
                id               TEXT PRIMARY KEY,
                created_at       TEXT NOT NULL,
                total_minor      INTEGER NOT NULL,
                shipping_address TEXT NOT NULL,
                geo_location     TEXT NULL
            );

            CREATE TABLE IF NOT EXISTS order_items (
                order_id    TEXT NOT NULL,
                product_id  TEXT NOT NULL,
                sku         TEXT NOT NULL,
                name        TEXT NOT NULL,
                price_minor INTEGER NOT NULL,
                quantity    INTEGER NOT NULL,
                PRIMARY KEY (order_id, product_id),
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                -- no FK on product_id: an order line is an immutable snapshot
            );
            SQL,
        );
    }

    public static function seed(\PDO $pdo): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO products (id, sku, name, price_minor, description)
             VALUES (:id, :sku, :name, :price_minor, :description)
             ON CONFLICT(sku) DO NOTHING',
        );
        assert($stmt instanceof \PDOStatement);

        foreach (self::catalog() as $product) {
            $stmt->execute([
                'id' => Uuid::v5(self::PRODUCT_NAMESPACE, $product['sku']),
                'sku' => $product['sku'],
                'name' => $product['name'],
                'price_minor' => $product['price_minor'],
                'description' => $product['description'],
            ]);
        }
    }

    /**
     * Demo catalog. Prices are in integer minor units (cents).
     *
     * @return list<array{sku: string, name: string, price_minor: int, description: ?string}>
     */
    private static function catalog(): array
    {
        return [
            [
                'sku' => 'SOAP-LAVENDER',
                'name' => 'Lavender soap',
                'price_minor' => 8900,
                'description' => 'Handmade lavender soap',
            ],
            [
                'sku' => 'SOAP-HONEY',
                'name' => 'Honey & oat soap',
                'price_minor' => 9500,
                'description' => 'Soothing honey and oat soap',
            ],
            [
                'sku' => 'BALM-SHEA',
                'name' => 'Shea butter balm',
                'price_minor' => 12900,
                'description' => 'Rich shea butter body balm',
            ],
            [
                'sku' => 'SALT-BATH',
                'name' => 'Bath salts',
                'price_minor' => 14900,
                'description' => 'Relaxing mineral bath salts',
            ],
            [
                'sku' => 'OIL-LAVENDER',
                'name' => 'Lavender essential oil',
                'price_minor' => 8990,
                'description' => 'Calming lavender essential oil',
            ],
        ];
    }
}
