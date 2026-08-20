<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Exception\CartItemNotFoundException;
use App\Domain\Exception\CartNotFoundException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Cart;
use App\Domain\Model\CartItem;
use App\Domain\Model\Product;
use App\Domain\Model\Quantity;
use App\Domain\Model\Sku;
use App\Domain\Repository\CartRepositoryInterface;
use App\Domain\Repository\ProductRepositoryInterface;
use App\Domain\Uuid;
use Psr\Log\LoggerInterface;

final readonly class CartService
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private ProductRepositoryInterface $products,
        private LoggerInterface $logger,
    ) {
    }

    public function create(): Cart
    {
        $cart = new Cart(Uuid::v4());
        $this->carts->save($cart);
        $this->logger->info('cart_created', ['cart_id' => $cart->id]);

        return $cart;
    }

    public function get(string $id): Cart
    {
        $cart = $this->carts->find($id);
        if ($cart === null) {
            throw new CartNotFoundException($id);
        }

        return $cart;
    }

    public function addItem(string $cartId, Sku $sku, Quantity $quantity): Cart
    {
        $cart = $this->get($cartId);

        $product = $this->products->find($sku);
        if ($product === null) {
            throw new ProductNotFoundException($sku->value);
        }

        $existing = $cart->findItem($sku);
        $items = $existing === null
            ? [...$cart->items(), new CartItem($product, $quantity)]
            : $this->replaceItem($cart->items(), $sku, new CartItem($product, $existing->quantity->add($quantity)));

        return $this->persist(new Cart($cart->id, $items));
    }

    public function removeItem(string $cartId, Sku $sku, ?Quantity $quantity): Cart
    {
        $cart = $this->get($cartId);

        $existing = $cart->findItem($sku);
        if ($existing === null) {
            throw new CartItemNotFoundException($sku->value);
        }

        if ($quantity === null || $quantity->isGreaterThanOrEqual($existing->quantity)) {
            $items = $this->removeItemBySku($cart->items(), $sku);
        } else {
            $remaining = new Quantity($existing->quantity->value - $quantity->value);
            $items = $this->replaceItem($cart->items(), $sku, new CartItem($existing->product, $remaining));
        }

        return $this->persist(new Cart($cart->id, $items));
    }

    private function persist(Cart $cart): Cart
    {
        $this->carts->save($cart);

        return $cart;
    }

    /**
     * @param list<CartItem> $items
     *
     * @return list<CartItem>
     */
    private function replaceItem(array $items, Sku $sku, CartItem $replacement): array
    {
        return array_map(
            static fn (CartItem $item): CartItem => $item->product->sku->equals($sku) ? $replacement : $item,
            $items,
        );
    }

    /**
     * @param list<CartItem> $items
     *
     * @return list<CartItem>
     */
    private function removeItemBySku(array $items, Sku $sku): array
    {
        return array_values(array_filter(
            $items,
            static fn (CartItem $item): bool => !$item->product->sku->equals($sku),
        ));
    }
}
