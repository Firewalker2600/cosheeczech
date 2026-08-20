<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Model\Money;
use App\Domain\Model\Order;
use App\Domain\Model\OrderItem;
use App\Domain\Model\Quantity;
use App\Domain\Model\Sku;
use App\Domain\Repository\OrderRepositoryInterface;

final readonly class SqliteOrderRepository implements OrderRepositoryInterface
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function find(string $id): ?Order
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute(['id' => $id]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row, $this->loadItems($id));
    }

    /** @return list<Order> */
    public function findAll(): array
    {
        $itemsByOrder = $this->loadAllItems();

        $stmt = $this->pdo->query('SELECT * FROM orders ORDER BY created_at DESC');
        assert($stmt instanceof \PDOStatement);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        $orders = [];
        foreach ($rows as $row) {
            $orders[] = $this->hydrate($row, $itemsByOrder[Row::string($row, 'id')] ?? []);
        }

        return $orders;
    }

    public function save(Order $order): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT OR REPLACE INTO orders (id, created_at, total_minor, shipping_address, geo_location)
                 VALUES (:id, :created_at, :total_minor, :shipping_address, :geo_location)',
            );
            assert($stmt instanceof \PDOStatement);
            $stmt->execute([
                'id' => $order->id,
                'created_at' => $order->createdAt->format(\DateTimeInterface::ATOM),
                'total_minor' => $order->total->minor,
                'shipping_address' => $order->shippingAddress,
                'geo_location' => $order->geoLocation,
            ]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, sku, name, price_minor, quantity)
                 VALUES (:order_id, :product_id, :sku, :name, :price_minor, :quantity)',
            );
            assert($stmt instanceof \PDOStatement);

            foreach ($order->items() as $item) {
                $stmt->execute([
                    'order_id' => $order->id,
                    'product_id' => $item->productId,
                    'sku' => $item->sku->value,
                    'name' => $item->name,
                    'price_minor' => $item->price->minor,
                    'quantity' => $item->quantity->value,
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param list<OrderItem>      $items
     */
    private function hydrate(array $row, array $items): Order
    {
        return new Order(
            id: Row::string($row, 'id'),
            createdAt: new \DateTimeImmutable(Row::string($row, 'created_at')),
            items: $items,
            total: new Money(Row::int($row, 'total_minor')),
            shippingAddress: Row::string($row, 'shipping_address'),
            geoLocation: Row::nullableString($row, 'geo_location'),
        );
    }

    /** @return list<OrderItem> */
    private function loadItems(string $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY sku');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute(['order_id' => $orderId]);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        return array_values(array_map(fn (array $row): OrderItem => $this->hydrateItem($row), $rows));
    }

    /** @return array<string, list<OrderItem>> */
    private function loadAllItems(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM order_items ORDER BY order_id, sku');
        assert($stmt instanceof \PDOStatement);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[Row::string($row, 'order_id')][] = $this->hydrateItem($row);
        }

        return $grouped;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateItem(array $row): OrderItem
    {
        return new OrderItem(
            productId: Row::string($row, 'product_id'),
            sku: new Sku(Row::string($row, 'sku')),
            name: Row::string($row, 'name'),
            price: new Money(Row::int($row, 'price_minor')),
            quantity: new Quantity(Row::int($row, 'quantity')),
        );
    }
}
