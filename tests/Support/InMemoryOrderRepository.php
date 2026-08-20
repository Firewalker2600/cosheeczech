<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Model\Order;
use App\Domain\Repository\OrderRepositoryInterface;

final class InMemoryOrderRepository implements OrderRepositoryInterface
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function find(string $id): ?Order
    {
        return $this->orders[$id] ?? null;
    }

    /** @return list<Order> */
    public function findAll(): array
    {
        return array_values($this->orders);
    }

    public function save(Order $order): void
    {
        $this->orders[$order->id] = $order;
    }
}
