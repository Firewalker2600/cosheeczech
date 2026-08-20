<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\Cart;
use App\Domain\Model\CartItem;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\Quantity;
use App\Domain\Model\Sku;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CartTest extends TestCase
{
    #[Test]
    public function emptyCartHasZeroCountsAndTotal(): void
    {
        $cart = new Cart('cart-1');

        self::assertSame(0, $cart->itemCount());
        self::assertSame(0, $cart->totalQuantity());
        self::assertSame(0, $cart->total()->minor);
    }

    #[Test]
    public function computesCountsQuantityAndTotalFromItems(): void
    {
        $cart = new Cart('cart-1', [
            new CartItem($this->product('A', 1000), new Quantity(2)),
            new CartItem($this->product('B', 2500), new Quantity(1)),
        ]);

        self::assertSame(2, $cart->itemCount());
        self::assertSame(3, $cart->totalQuantity());
        self::assertSame(4500, $cart->total()->minor);
    }

    #[Test]
    public function findsAnItemBySku(): void
    {
        $cart = new Cart('cart-1', [
            new CartItem($this->product('A', 1000), new Quantity(1)),
        ]);

        $item = $cart->findItem(new Sku('A'));

        self::assertNotNull($item);
        self::assertSame('A', $item->product->sku->value);
        self::assertNull($cart->findItem(new Sku('Z')));
    }

    private function product(string $sku, int $priceMinor): Product
    {
        return new Product('prod-' . $sku, new Sku($sku), 'Product ' . $sku, new Money($priceMinor));
    }
}
