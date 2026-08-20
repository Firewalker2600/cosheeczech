<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Application\GeocoderInterface;
use App\Domain\Exception\CartNotFoundException;
use App\Domain\Exception\EmptyCartException;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Model\CartItem;
use App\Domain\Model\Order;
use App\Domain\Model\OrderItem;
use App\Domain\Repository\CartRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Uuid;
use Psr\Log\LoggerInterface;

final readonly class OrderService
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private OrderRepositoryInterface $orders,
        private GeocoderInterface $geocoder,
        private LoggerInterface $logger,
    ) {
    }

    public function createOrder(string $cartId, string $shippingAddress): Order
    {
        $cart = $this->carts->find($cartId);
        if ($cart === null) {
            throw new CartNotFoundException($cartId);
        }

        if ($cart->items() === []) {
            throw new EmptyCartException($cartId);
        }

        $geoLocation = $this->geocoder->geocode($shippingAddress);

        $order = new Order(
            id: Uuid::v4(),
            createdAt: new \DateTimeImmutable(),
            items: $this->toOrderItems($cart->items()),
            total: $cart->total(),
            shippingAddress: $shippingAddress,
            geoLocation: $geoLocation?->toString(),
        );

        $this->orders->save($order);

        $this->logger->info('order_created', [
            'order_id' => $order->id,
            'cart_id' => $cartId,
            'total_minor' => $order->total->minor,
            'item_count' => count($order->items()),
            'geocoded' => $geoLocation !== null,
        ]);

        return $order;
    }

    public function get(string $id): Order
    {
        $order = $this->orders->find($id);
        if ($order === null) {
            throw new OrderNotFoundException($id);
        }

        return $order;
    }

    /** @return list<Order> */
    public function getAll(): array
    {
        return $this->orders->findAll();
    }

    /**
     * @param list<CartItem> $items
     *
     * @return list<OrderItem>
     */
    private function toOrderItems(array $items): array
    {
        return array_map(
            static fn (CartItem $item): OrderItem => new OrderItem(
                productId: $item->product->id,
                sku: $item->product->sku,
                name: $item->product->name,
                price: $item->product->price,
                quantity: $item->quantity,
            ),
            $items,
        );
    }
}
