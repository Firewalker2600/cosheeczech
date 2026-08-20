<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\Money;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    #[Test]
    public function storesMinorUnitsAndExposesFloat(): void
    {
        $money = new Money(1234);

        self::assertSame(1234, $money->minor);
        self::assertSame(12.34, $money->toFloat());
    }

    #[Test]
    public function convertsFromFloatWithRounding(): void
    {
        self::assertSame(1234, Money::fromFloat(12.34)->minor);
    }

    #[Test]
    public function multipliesByAnIntegerFactor(): void
    {
        self::assertSame(26700, (new Money(8900))->multiply(3)->minor);
    }

    #[Test]
    public function addsTwoAmounts(): void
    {
        self::assertSame(18400, (new Money(8900))->add(new Money(9500))->minor);
    }

    #[Test]
    public function accumulatesWithoutFloatDrift(): void
    {
        // 0.10 added 10 times must be exactly 1.00 — the legacy float bug.
        $sum = new Money(0);
        for ($i = 0; $i < 10; $i++) {
            $sum = $sum->add(new Money(10));
        }

        self::assertSame(100, $sum->minor);
        self::assertSame(1.0, $sum->toFloat());
    }
}
