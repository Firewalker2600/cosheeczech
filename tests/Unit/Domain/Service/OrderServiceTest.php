<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Exception\CartNotFoundException;
use App\Domain\Exception\EmptyCartException;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Model\Cart;
use App\Domain\Model\CartItem;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\Quantity;
use App\Domain\Model\Sku;
use App\Domain\Service\OrderService;
use App\Infrastructure\Geocoding\NoopGeocoder;
use App\Tests\Support\InMemoryCartRepository;
use App\Tests\Support\InMemoryOrderRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class OrderServiceTest extends TestCase
{
    private OrderService $service;
    private InMemoryCartRepository $carts;
    private InMemoryOrderRepository $orders;

    protected function setUp(): void
    {
        $this->carts = new InMemoryCartRepository();
        $this->orders = new InMemoryOrderRepository();

        $this->service = new OrderService($this->carts, $this->orders, new NoopGeocoder(), new NullLogger());
    }

    #[Test]
    public function createsAnOrderFromACartWithCorrectTotals(): void
    {
        $cart = $this->cartWithItems();

        $order = $this->service->createOrder($cart->id, 'Prague 1');

        self::assertSame(4500, $order->total->minor);
        self::assertCount(2, $order->items());
        self::assertSame('Prague 1', $order->shippingAddress);
        self::assertNull($order->geoLocation);
        self::assertNotNull($this->orders->find($order->id));
    }

    #[Test]
    public function snapshotsProductDataIntoOrderItems(): void
    {
        $cart = $this->cartWithItems();

        $order = $this->service->createOrder($cart->id, 'Prague 1');

        $first = $order->items()[0];
        self::assertSame('p-A', $first->productId);
        self::assertSame('A', $first->sku->value);
        self::assertSame('Product A', $first->name);
        self::assertSame(1000, $first->price->minor);
    }

    #[Test]
    public function throwsWhenCartIsNotFound(): void
    {
        $this->expectException(CartNotFoundException::class);
        $this->service->createOrder('missing', 'Prague');
    }

    #[Test]
    public function throwsWhenCartIsEmpty(): void
    {
        $cart = new Cart('empty');
        $this->carts->save($cart);

        $this->expectException(EmptyCartException::class);
        $this->service->createOrder($cart->id, 'Prague');
    }

    #[Test]
    public function getsAnOrderById(): void
    {
        $cart = $this->cartWithItems();
        $order = $this->service->createOrder($cart->id, 'Prague');

        self::assertSame($order->id, $this->service->get($order->id)->id);
    }

    #[Test]
    public function throwsWhenOrderIsNotFound(): void
    {
        $this->expectException(OrderNotFoundException::class);
        $this->service->get('missing');
    }

    #[Test]
    public function listsAllOrders(): void
    {
        $cart = $this->cartWithItems();
        $this->service->createOrder($cart->id, 'Prague');

        self::assertCount(1, $this->service->getAll());
    }

    private function cartWithItems(): Cart
    {
        $cart = new Cart('cart-1', [
            new CartItem(new Product('p-A', new Sku('A'), 'Product A', new Money(1000)), new Quantity(2)),
            new CartItem(new Product('p-B', new Sku('B'), 'Product B', new Money(2500)), new Quantity(1)),
        ]);
        $this->carts->save($cart);

        return $cart;
    }
}
