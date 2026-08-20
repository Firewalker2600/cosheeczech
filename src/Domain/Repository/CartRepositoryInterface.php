<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Cart;

interface CartRepositoryInterface
{
    public function find(string $id): ?Cart;

    public function save(Cart $cart): void;
}
