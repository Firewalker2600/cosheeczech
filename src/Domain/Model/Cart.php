<?php

declare(strict_types=1);

namespace App\Domain\Model;

final readonly class Cart
{
    /**
     * @param list<CartItem> $items
     */
    public function __construct(
        public string $id,
        public array $items = [],
    ) {
    }

    /** @return list<CartItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function itemCount(): int
    {
        return count($this->items);
    }

    public function totalQuantity(): int
    {
        return array_sum(
            array_map(static fn (CartItem $item): int => $item->quantity->value, $this->items),
        );
    }

    public function total(): Money
    {
        $total = new Money(0);
        foreach ($this->items as $item) {
            $total = $total->add($item->total());
        }

        return $total;
    }

    public function findItem(Sku $sku): ?CartItem
    {
        foreach ($this->items as $item) {
            if ($item->product->sku->equals($sku)) {
                return $item;
            }
        }

        return null;
    }
}
