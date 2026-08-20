<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Model\Cart;
use App\Domain\Model\CartItem;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\Quantity;
use App\Domain\Model\Sku;
use App\Domain\Repository\CartRepositoryInterface;

final readonly class SqliteCartRepository implements CartRepositoryInterface
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function find(string $id): ?Cart
    {
        $stmt = $this->pdo->prepare('SELECT id FROM carts WHERE id = :id');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute(['id' => $id]);

        if ($stmt->fetch() === false) {
            return null;
        }

        return new Cart($id, $this->loadItems($id));
    }

    public function save(Cart $cart): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('INSERT OR REPLACE INTO carts (id) VALUES (:id)');
            assert($stmt instanceof \PDOStatement);
            $stmt->execute(['id' => $cart->id]);

            $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
            assert($stmt instanceof \PDOStatement);
            $stmt->execute(['cart_id' => $cart->id]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)',
            );
            assert($stmt instanceof \PDOStatement);

            foreach ($cart->items() as $item) {
                $stmt->execute([
                    'cart_id' => $cart->id,
                    'product_id' => $item->product->id,
                    'quantity' => $item->quantity->value,
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

    /** @return list<CartItem> */
    private function loadItems(string $cartId): array
    {
        $stmt = $this->pdo->prepare(
            <<<'SQL'
            SELECT ci.product_id, ci.quantity, p.id, p.sku, p.name, p.price_minor, p.description
            FROM cart_items ci
            JOIN products p ON p.id = ci.product_id
            WHERE ci.cart_id = :cart_id
            ORDER BY p.sku
            SQL,
        );
        assert($stmt instanceof \PDOStatement);
        $stmt->execute(['cart_id' => $cartId]);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $items[] = new CartItem(
                product: new Product(
                    id: Row::string($row, 'id'),
                    sku: new Sku(Row::string($row, 'sku')),
                    name: Row::string($row, 'name'),
                    price: new Money(Row::int($row, 'price_minor')),
                    description: Row::nullableString($row, 'description'),
                ),
                quantity: new Quantity(Row::int($row, 'quantity')),
            );
        }

        return $items;
    }
}
