<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Stock Keeping Unit — a non-empty product identifier.
 */
final readonly class Sku
{
    public function __construct(public string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('SKU must not be empty');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
