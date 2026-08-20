<?php

declare(strict_types=1);

namespace App\Http\Presenter;

use App\Domain\Model\Order;
use App\Domain\Model\OrderItem;

/**
 * Maps an Order to the OpenAPI `Order` schema (snake_case, float money, RFC3339 date).
 */
final class OrderPresenter
{
    /** @return array<string, mixed> */
    public static function toArray(Order $order): array
    {
        return [
            'id' => $order->id,
            'created_at' => $order->createdAt->format(\DateTimeInterface::ATOM),
            'items' => array_map(self::presentItem(...), $order->items()),
            'total' => $order->total->toFloat(),
            'shipping_address' => $order->shippingAddress,
            'geo_location' => $order->geoLocation,
        ];
    }

    /** @return array<string, mixed> */
    private static function presentItem(OrderItem $item): array
    {
        return [
            'sku' => $item->sku->value,
            'name' => $item->name,
            'price' => $item->price->toFloat(),
            'quantity' => $item->quantity->value,
            'total' => $item->total()->toFloat(),
        ];
    }
}
