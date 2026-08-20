<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Geocoding;

use App\Infrastructure\Geocoding\NominatimGeocoder;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class NominatimGeocoderTest extends TestCase
{
    #[Test]
    public function geocodesAnAddressAndExtractsCoordinates(): void
    {
        $geocoder = $this->geocoder(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            [
                'lat' => '50.0755',
                'lon' => '14.4378',
                'display_name' => 'Prague, Czechia',
            ],
        ], JSON_THROW_ON_ERROR)));

        $result = $geocoder->geocode('Prague');

        self::assertNotNull($result);
        self::assertSame(50.0755, $result->latitude);
        self::assertSame(14.4378, $result->longitude);
        self::assertSame('Prague, Czechia', $result->displayName);
    }

    #[Test]
    public function returnsNullWhenTheAddressIsUnknown(): void
    {
        $geocoder = $this->geocoder(new Response(200, ['Content-Type' => 'application/json'], '[]'));

        self::assertNull($geocoder->geocode('Nonexistent Place 12345'));
    }

    #[Test]
    public function returnsNullOnANon200Response(): void
    {
        $geocoder = $this->geocoder(new Response(503));

        self::assertNull($geocoder->geocode('Prague'));
    }

    #[Test]
    public function returnsNullWhenTheResponseIsMalformed(): void
    {
        $geocoder = $this->geocoder(new Response(200, ['Content-Type' => 'application/json'], '{"nope": true}'));

        self::assertNull($geocoder->geocode('Prague'));
    }

    private function geocoder(Response $response): NominatimGeocoder
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([$response]))]);

        return new NominatimGeocoder(
            client: $client,
            requestFactory: new HttpFactory(),
            logger: new NullLogger(),
        );
    }
}
