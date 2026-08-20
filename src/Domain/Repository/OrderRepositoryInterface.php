<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Order;

interface OrderRepositoryInterface
{
    public function find(string $id): ?Order;

    /** @return list<Order> */
    public function findAll(): array;

    public function save(Order $order): void;
}
