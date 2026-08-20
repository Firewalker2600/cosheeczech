<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

final class CartApiTest extends ApiTestCase
{
    #[Test]
    public function createsAnEmptyCart(): void
    {
        $body = $this->createCart();

        self::assertArrayHasKey('id', $body);
        self::assertSame([], $body['items']);
        self::assertSame(0, $body['item_count']);
        self::assertSame(0, $body['total_quantity']);
        self::assertEquals(0.0, $body['total']);
    }

    #[Test]
    public function getsACartById(): void
    {
        $cart = $this->createCart();

        $response = $this->request('GET', '/api/cart/' . self::stringAt($cart, 'id'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(self::stringAt($cart, 'id'), self::stringAt($this->decode($response), 'id'));
    }

    #[Test]
    public function returns404ForAnUnknownCart(): void
    {
        $response = $this->request('GET', '/api/cart/missing');

        self::assertSame(404, $response->getStatusCode());
        self::assertArrayHasKey('error', $this->decode($response));
    }

    #[Test]
    public function addsAProductAndReturnsCorrectTotals(): void
    {
        $cart = $this->createCart();

        $body = $this->addToCart(self::stringAt($cart, 'id'), 'SOAP-LAVENDER', 2);

        self::assertSame(1, $body['item_count']);
        self::assertSame(2, $body['total_quantity']);
        self::assertEquals(178.0, $body['total']);

        $item = self::listAt($body, 'items')[0];
        self::assertSame('SOAP-LAVENDER', self::stringAt(self::mapAt($item, 'product'), 'sku'));
        self::assertEquals(89.0, self::mapAt($item, 'product')['price']);
    }

    #[Test]
    public function returns404ForAnUnknownSku(): void
    {
        $cart = $this->createCart();

        $response = $this->request('POST', '/api/cart/add', ['cart_id' => self::stringAt($cart, 'id'), 'sku' => 'NOPE']);

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function returns404WhenAddingToAnUnknownCart(): void
    {
        $response = $this->request('POST', '/api/cart/add', ['cart_id' => 'missing', 'sku' => 'SOAP-LAVENDER']);

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function returns400ForMissingRequiredFields(): void
    {
        $cart = $this->createCart();

        $missingCartId = $this->request('POST', '/api/cart/add', ['sku' => 'SOAP-LAVENDER']);
        $missingSku = $this->request('POST', '/api/cart/add', ['cart_id' => self::stringAt($cart, 'id')]);

        self::assertSame(400, $missingCartId->getStatusCode());
        self::assertSame(400, $missingSku->getStatusCode());
    }

    #[Test]
    public function returns400ForAnInvalidQuantity(): void
    {
        $cart = $this->createCart();

        $response = $this->request('POST', '/api/cart/add', [
            'cart_id' => self::stringAt($cart, 'id'),
            'sku' => 'SOAP-LAVENDER',
            'quantity' => 0,
        ]);

        self::assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function removesAProductCompletely(): void
    {
        $cart = $this->createCart();
        $this->addToCart(self::stringAt($cart, 'id'), 'SOAP-LAVENDER', 3);

        $response = $this->request('POST', '/api/cart/remove', ['cart_id' => self::stringAt($cart, 'id'), 'sku' => 'SOAP-LAVENDER']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(0, $this->decode($response)['item_count']);
    }

    #[Test]
    public function removesOnlyAPartialQuantity(): void
    {
        $cart = $this->createCart();
        $this->addToCart(self::stringAt($cart, 'id'), 'SOAP-LAVENDER', 3);

        $response = $this->request('POST', '/api/cart/remove', [
            'cart_id' => self::stringAt($cart, 'id'),
            'sku' => 'SOAP-LAVENDER',
            'quantity' => 1,
        ]);

        self::assertSame(2, $this->decode($response)['total_quantity']);
    }

    #[Test]
    public function returns404WhenRemovingAnSkuNotInTheCart(): void
    {
        $cart = $this->createCart();

        $response = $this->request('POST', '/api/cart/remove', ['cart_id' => self::stringAt($cart, 'id'), 'sku' => 'SOAP-LAVENDER']);

        self::assertSame(404, $response->getStatusCode());
    }
}
