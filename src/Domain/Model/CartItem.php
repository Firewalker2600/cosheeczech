<?php

declare(strict_types=1);

namespace App\Domain\Model;

final readonly class CartItem
{
    public function __construct(
        public Product $product,
        public Quantity $quantity,
    ) {
    }

    public function total(): Money
    {
        return $this->product->price->multiply($this->quantity->value);
    }
}
