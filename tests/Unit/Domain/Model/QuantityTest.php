<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\Quantity;
use App\Domain\Model\Sku;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    #[Test]
    public function acceptsPositiveQuantities(): void
    {
        self::assertSame(3, (new Quantity(3))->value);
        self::assertSame(1, (new Quantity(1))->value);
    }

    #[Test]
    public function rejectsZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Quantity(0);
    }

    #[Test]
    public function rejectsNegativeQuantities(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Quantity(-2);
    }

    #[Test]
    public function addsTwoQuantities(): void
    {
        self::assertSame(5, (new Quantity(2))->add(new Quantity(3))->value);
    }

    #[Test]
    public function comparesMagnitudes(): void
    {
        self::assertTrue((new Quantity(3))->isGreaterThanOrEqual(new Quantity(1)));
        self::assertFalse((new Quantity(1))->isGreaterThanOrEqual(new Quantity(3)));
    }
}

final class SkuTest extends TestCase
{
    #[Test]
    public function acceptsNonEmptySku(): void
    {
        self::assertSame('SOAP-1', (new Sku('SOAP-1'))->value);
    }

    #[Test]
    public function rejectsEmptySku(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Sku('');
    }

    #[Test]
    public function rejectsWhitespaceOnlySku(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Sku('   ');
    }

    #[Test]
    public function comparesByValue(): void
    {
        self::assertTrue((new Sku('A'))->equals(new Sku('A')));
        self::assertFalse((new Sku('A'))->equals(new Sku('B')));
    }
}
