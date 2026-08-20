<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Immutable snapshot of a product at the time an order was placed.
 */
final readonly class OrderItem
{
    public function __construct(
        public string $productId,
        public Sku $sku,
        public string $name,
        public Money $price,
        public Quantity $quantity,
    ) {
    }

    public function total(): Money
    {
        return $this->price->multiply($this->quantity->value);
    }
}
