<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Exception\CartItemNotFoundException;
use App\Domain\Exception\CartNotFoundException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\Quantity;
use App\Domain\Model\Sku;
use App\Domain\Service\CartService;
use App\Tests\Support\InMemoryCartRepository;
use App\Tests\Support\InMemoryProductRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CartServiceTest extends TestCase
{
    private CartService $service;
    private InMemoryCartRepository $carts;

    protected function setUp(): void
    {
        $this->carts = new InMemoryCartRepository();

        $products = new InMemoryProductRepository();
        $products->save(new Product('p-SOAP-1', new Sku('SOAP-1'), 'Soap', new Money(8900)));

        $this->service = new CartService($this->carts, $products, new NullLogger());
    }

    #[Test]
    public function createsAndPersistsAnEmptyCart(): void
    {
        $cart = $this->service->create();

        self::assertSame(0, $cart->itemCount());
        self::assertNotNull($this->carts->find($cart->id));
    }

    #[Test]
    public function throwsWhenGettingAnUnknownCart(): void
    {
        $this->expectException(CartNotFoundException::class);
        $this->service->get('missing');
    }

    #[Test]
    public function addsAProductToTheCart(): void
    {
        $cart = $this->service->create();

        $updated = $this->service->addItem($cart->id, new Sku('SOAP-1'), new Quantity(2));

        self::assertSame(1, $updated->itemCount());
        self::assertSame(2, $updated->totalQuantity());
        self::assertSame(17800, $updated->total()->minor);
    }

    #[Test]
    public function incrementsQuantityWhenAddingTheSameSku(): void
    {
        $cart = $this->service->create();
        $this->service->addItem($cart->id, new Sku('SOAP-1'), new Quantity(1));

        $updated = $this->service->addItem($cart->id, new Sku('SOAP-1'), new Quantity(2));

        self::assertSame(1, $updated->itemCount());
        self::assertSame(3, $updated->totalQuantity());
    }

    #[Test]
    public function throwsWhenAddingAnUnknownProduct(): void
    {
        $cart = $this->service->create();

        $this->expectException(ProductNotFoundException::class);
        $this->service->addItem($cart->id, new Sku('UNKNOWN'), new Quantity(1));
    }

    #[Test]
    public function removesAnItemCompletelyWhenNoQuantityIsGiven(): void
    {
        $cart = $this->service->create();
        $this->service->addItem($cart->id, new Sku('SOAP-1'), new Quantity(3));

        $updated = $this->service->removeItem($cart->id, new Sku('SOAP-1'), null);

        self::assertSame(0, $updated->itemCount());
    }

    #[Test]
    public function removesOnlyAPartialQuantity(): void
    {
        $cart = $this->service->create();
        $this->service->addItem($cart->id, new Sku('SOAP-1'), new Quantity(3));

        $updated = $this->service->removeItem($cart->id, new Sku('SOAP-1'), new Quantity(1));

        self::assertSame(1, $updated->itemCount());
        self::assertSame(2, $updated->totalQuantity());
    }

    #[Test]
    public function removesTheItemWhenRemovingAtLeastTheFullQuantity(): void
    {
        $cart = $this->service->create();
        $this->service->addItem($cart->id, new Sku('SOAP-1'), new Quantity(3));

        $updated = $this->service->removeItem($cart->id, new Sku('SOAP-1'), new Quantity(5));

        self::assertSame(0, $updated->itemCount());
    }

    #[Test]
    public function throwsWhenRemovingAnSkuThatIsNotInTheCart(): void
    {
        $cart = $this->service->create();

        $this->expectException(CartItemNotFoundException::class);
        $this->service->removeItem($cart->id, new Sku('SOAP-1'), null);
    }

    #[Test]
    public function throwsWhenRemovingFromAnUnknownCart(): void
    {
        $this->expectException(CartNotFoundException::class);
        $this->service->removeItem('missing', new Sku('SOAP-1'), null);
    }
}
