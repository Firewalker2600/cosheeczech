<?php

declare(strict_types=1);

namespace App\Domain\Model;

final readonly class Order
{
    /**
     * @param list<OrderItem> $items
     */
    public function __construct(
        public string $id,
        public \DateTimeImmutable $createdAt,
        public array $items,
        public Money $total,
        public string $shippingAddress,
        public ?string $geoLocation = null,
    ) {
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }
}
