<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Model\Quantity;
use App\Domain\Model\Sku;
use App\Domain\Service\CartService;
use App\Http\Json;
use App\Http\Presenter\CartPresenter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CartController
{
    public function __construct(private CartService $carts, private Json $json)
    {
    }

    /**
     * @param array<string, mixed> $args
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->json->write($response, CartPresenter::toArray($this->carts->create()));
    }

    /**
     * @param array<string, mixed> $args
     */
    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? null;
        if (!is_string($id) || $id === '') {
            return $this->json->write($response, ['error' => 'Missing cart ID'], 400);
        }

        return $this->json->write($response, CartPresenter::toArray($this->carts->get($id)));
    }

    /**
     * @param array<string, mixed> $args
     */
    public function addItem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $this->parseBody($request);
        if ($body === null) {
            return $this->json->write($response, ['error' => 'Invalid JSON body'], 400);
        }

        $cartId = $body['cart_id'] ?? null;
        $sku = $body['sku'] ?? null;

        if (!is_string($cartId) || trim($cartId) === '') {
            return $this->json->write($response, ['error' => 'Missing required field: cart_id'], 400);
        }

        if (!is_string($sku) || trim($sku) === '') {
            return $this->json->write($response, ['error' => 'Missing required field: sku'], 400);
        }

        $quantity = $body['quantity'] ?? 1;
        if (!is_int($quantity) || $quantity < 1) {
            return $this->json->write($response, ['error' => 'quantity must be a positive integer'], 400);
        }

        $cart = $this->carts->addItem($cartId, new Sku($sku), new Quantity($quantity));

        return $this->json->write($response, CartPresenter::toArray($cart));
    }

    /**
     * @param array<string, mixed> $args
     */
    public function removeItem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $this->parseBody($request);
        if ($body === null) {
            return $this->json->write($response, ['error' => 'Invalid JSON body'], 400);
        }

        $cartId = $body['cart_id'] ?? null;
        $sku = $body['sku'] ?? null;

        if (!is_string($cartId) || trim($cartId) === '') {
            return $this->json->write($response, ['error' => 'Missing required field: cart_id'], 400);
        }

        if (!is_string($sku) || trim($sku) === '') {
            return $this->json->write($response, ['error' => 'Missing required field: sku'], 400);
        }

        $quantity = null;
        $rawQuantity = $body['quantity'] ?? null;
        if ($rawQuantity !== null) {
            if (!is_int($rawQuantity) || $rawQuantity < 1) {
                return $this->json->write($response, ['error' => 'quantity must be a positive integer'], 400);
            }
            $quantity = new Quantity($rawQuantity);
        }

        $cart = $this->carts->removeItem($cartId, new Sku($sku), $quantity);

        return $this->json->write($response, CartPresenter::toArray($cart));
    }

    /** @return array<string, mixed>|null */
    private function parseBody(ServerRequestInterface $request): ?array
    {
        $parsed = $request->getParsedBody();

        return is_array($parsed) ? $parsed : null;
    }
}
