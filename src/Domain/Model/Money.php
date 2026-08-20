<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Exact money amount stored in integer minor units (cents).
 * Avoids the floating-point accumulation bug present in the legacy code.
 */
final readonly class Money
{
    public function __construct(public int $minor)
    {
    }

    public static function fromFloat(float $amount): self
    {
        return new self((int) round($amount * 100));
    }

    public function toFloat(): float
    {
        return $this->minor / 100;
    }

    public function multiply(int $factor): self
    {
        return new self($this->minor * $factor);
    }

    public function add(self $other): self
    {
        return new self($this->minor + $other->minor);
    }
}
