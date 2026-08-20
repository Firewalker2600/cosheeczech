<?php

declare(strict_types=1);

namespace App\Domain\Model;

final readonly class Product
{
    public function __construct(
        public string $id,
        public Sku $sku,
        public string $name,
        public Money $price,
        public ?string $description = null,
    ) {
    }
}
