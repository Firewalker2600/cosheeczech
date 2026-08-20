<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

final class OrderApiTest extends ApiTestCase
{
    #[Test]
    public function createsAnOrderFromACart(): void
    {
        $cart = $this->createCart();
        $this->addToCart(self::stringAt($cart, 'id'), 'SOAP-LAVENDER', 2);

        $response = $this->request('POST', '/api/orders', [
            'cart_id' => self::stringAt($cart, 'id'),
            'shipping_address' => 'Prague 1',
        ]);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);

        self::assertArrayHasKey('id', $body);
        self::assertArrayHasKey('created_at', $body);
        self::assertEquals(178.0, $body['total']);
        self::assertSame('Prague 1', $body['shipping_address']);
        self::assertNull($body['geo_location']);

        $items = self::listAt($body, 'items');
        self::assertCount(1, $items);
        self::assertSame('SOAP-LAVENDER', self::stringAt($items[0], 'sku'));
    }

    #[Test]
    public function returns400WhenTheCartIsEmpty(): void
    {
        $cart = $this->createCart();

        $response = $this->request('POST', '/api/orders', [
            'cart_id' => self::stringAt($cart, 'id'),
            'shipping_address' => 'Prague',
        ]);

        self::assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function returns404ForAnUnknownCart(): void
    {
        $response = $this->request('POST', '/api/orders', [
            'cart_id' => 'missing',
            'shipping_address' => 'Prague',
        ]);

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function returns400ForMissingRequiredFields(): void
    {
        $cart = $this->createCart();

        $missingCartId = $this->request('POST', '/api/orders', ['shipping_address' => 'Prague']);
        $missingAddress = $this->request('POST', '/api/orders', ['cart_id' => self::stringAt($cart, 'id')]);

        self::assertSame(400, $missingCartId->getStatusCode());
        self::assertSame(400, $missingAddress->getStatusCode());
    }

    #[Test]
    public function listsAllOrders(): void
    {
        $cart = $this->createCart();
        $this->addToCart(self::stringAt($cart, 'id'), 'SOAP-LAVENDER', 1);
        $this->request('POST', '/api/orders', ['cart_id' => self::stringAt($cart, 'id'), 'shipping_address' => 'Prague']);

        $response = $this->request('GET', '/api/orders');

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, self::listAt($this->decode($response), 'orders'));
    }

    #[Test]
    public function getsAnOrderById(): void
    {
        $cart = $this->createCart();
        $this->addToCart(self::stringAt($cart, 'id'), 'SOAP-LAVENDER', 1);
        $created = $this->decode($this->request('POST', '/api/orders', [
            'cart_id' => self::stringAt($cart, 'id'),
            'shipping_address' => 'Prague',
        ]));

        $response = $this->request('GET', '/api/orders/' . self::stringAt($created, 'id'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(self::stringAt($created, 'id'), self::stringAt($this->decode($response), 'id'));
    }

    #[Test]
    public function returns404ForAnUnknownOrder(): void
    {
        $response = $this->request('GET', '/api/orders/missing');

        self::assertSame(404, $response->getStatusCode());
    }
}
