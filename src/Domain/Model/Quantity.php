<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * A positive item quantity (>= 1).
 */
final readonly class Quantity
{
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw new \InvalidArgumentException(
                sprintf('Quantity must be at least 1, got %d', $value),
            );
        }
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->value >= $other->value;
    }
}
