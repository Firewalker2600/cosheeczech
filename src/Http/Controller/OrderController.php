<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Service\OrderService;
use App\Http\Json;
use App\Http\Presenter\OrderPresenter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class OrderController
{
    public function __construct(private OrderService $orders, private Json $json)
    {
    }

    /**
     * @param array<string, mixed> $args
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $this->parseBody($request);
        if ($body === null) {
            return $this->json->write($response, ['error' => 'Invalid JSON body'], 400);
        }

        $cartId = $body['cart_id'] ?? null;
        $shippingAddress = $body['shipping_address'] ?? null;

        if (!is_string($cartId) || trim($cartId) === '') {
            return $this->json->write($response, ['error' => 'Missing required field: cart_id'], 400);
        }

        if (!is_string($shippingAddress) || trim($shippingAddress) === '') {
            return $this->json->write($response, ['error' => 'Missing required field: shipping_address'], 400);
        }

        $order = $this->orders->createOrder($cartId, $shippingAddress);

        return $this->json->write($response, OrderPresenter::toArray($order));
    }

    /**
     * @param array<string, mixed> $args
     */
    public function getAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->json->write($response, [
            'orders' => array_map(OrderPresenter::toArray(...), $this->orders->getAll()),
        ]);
    }

    /**
     * @param array<string, mixed> $args
     */
    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? null;
        if (!is_string($id) || $id === '') {
            return $this->json->write($response, ['error' => 'Missing order ID'], 400);
        }

        return $this->json->write($response, OrderPresenter::toArray($this->orders->get($id)));
    }

    /** @return array<string, mixed>|null */
    private function parseBody(ServerRequestInterface $request): ?array
    {
        $parsed = $request->getParsedBody();

        return is_array($parsed) ? $parsed : null;
    }
}
