<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Http\ApplicationFactory;
use App\Http\ContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

abstract class ApiTestCase extends TestCase
{
    /** @var App<ContainerInterface> */
    protected App $app;

    protected function setUp(): void
    {
        $container = ContainerFactory::create([
            'dsn' => 'sqlite::memory:',
            'geocoder' => 'noop',
            'logger' => new NullLogger(),
        ]);

        $this->app = ApplicationFactory::create($container);
    }

    /**
     * @param array<string, mixed>|null $json
     */
    protected function request(string $method, string $uri, ?array $json = null): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);

        if ($json !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody((new StreamFactory())->createStream(json_encode($json, JSON_THROW_ON_ERROR)));
        }

        return $this->app->handle($request);
    }

    /** @return array<string, mixed> */
    protected function decode(ResponseInterface $response): array
    {
        $data = json_decode((string) $response->getBody(), true);

        if (!is_array($data)) {
            self::fail('Expected a JSON object, got: ' . (string) $response->getBody());
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /** @return array<string, mixed> */
    protected function createCart(): array
    {
        $response = $this->request('POST', '/api/cart');
        self::assertSame(200, $response->getStatusCode());

        return $this->decode($response);
    }

    /** @return array<string, mixed> */
    protected function addToCart(string $cartId, string $sku, int $quantity = 1): array
    {
        $response = $this->request('POST', '/api/cart/add', [
            'cart_id' => $cartId,
            'sku' => $sku,
            'quantity' => $quantity,
        ]);
        self::assertSame(200, $response->getStatusCode());

        return $this->decode($response);
    }

    /** @param array<string, mixed> $data */
    protected static function stringAt(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        self::assertIsString($value, "Expected '$key' to be a string");

        return $value;
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed> */
    protected static function mapAt(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        self::assertIsArray($value, "Expected '$key' to be an object");
        /** @var array<string, mixed> $value */

        return $value;
    }

    /** @param array<string, mixed> $data
     *  @return list<array<string, mixed>> */
    protected static function listAt(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        self::assertIsArray($value, "Expected '$key' to be an array");
        self::assertIsList($value);
        /** @var list<array<string, mixed>> $value */

        return $value;
    }
}
