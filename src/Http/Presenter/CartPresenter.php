<?php

declare(strict_types=1);

namespace App\Http\Presenter;

use App\Domain\Model\Cart;
use App\Domain\Model\CartItem;
use App\Domain\Model\Product;

/**
 * Maps a Cart to the OpenAPI `Cart` schema (snake_case, float money).
 */
final class CartPresenter
{
    /** @return array<string, mixed> */
    public static function toArray(Cart $cart): array
    {
        return [
            'id' => $cart->id,
            'items' => array_map(self::presentItem(...), $cart->items()),
            'item_count' => $cart->itemCount(),
            'total_quantity' => $cart->totalQuantity(),
            'total' => $cart->total()->toFloat(),
        ];
    }

    /** @return array<string, mixed> */
    private static function presentItem(CartItem $item): array
    {
        return [
            'product' => self::presentProduct($item->product),
            'quantity' => $item->quantity->value,
            'total' => $item->total()->toFloat(),
        ];
    }

    /** @return array<string, mixed> */
    private static function presentProduct(Product $product): array
    {
        return [
            'sku' => $product->sku->value,
            'name' => $product->name,
            'price' => $product->price->toFloat(),
            'description' => $product->description,
        ];
    }
}
