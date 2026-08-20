<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Model\Cart;
use App\Domain\Repository\CartRepositoryInterface;

final class InMemoryCartRepository implements CartRepositoryInterface
{
    /** @var array<string, Cart> */
    private array $carts = [];

    public function find(string $id): ?Cart
    {
        return $this->carts[$id] ?? null;
    }

    public function save(Cart $cart): void
    {
        $this->carts[$cart->id] = $cart;
    }
}
