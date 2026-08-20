<?php

declare(strict_types=1);

namespace App\Infrastructure\Geocoding;

use App\Application\GeocoderInterface;
use App\Domain\Model\GeoLocation;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Geocodes a shipping address using the free OpenStreetMap Nominatim service.
 * Best-effort: returns null on network failure or unknown address (and logs it).
 */
final readonly class NominatimGeocoder implements GeocoderInterface
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private LoggerInterface $logger,
        private string $baseUrl = 'https://nominatim.openstreetmap.org/search',
        private string $userAgent = 'cosheeczech/1.0',
    ) {
    }

    public function geocode(string $address): ?GeoLocation
    {
        $uri = $this->baseUrl . '?' . http_build_query([
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
        ]);

        $request = $this->requestFactory->createRequest('GET', $uri)
            ->withHeader('User-Agent', $this->userAgent)
            ->withHeader('Accept', 'application/json');

        try {
            $response = $this->client->sendRequest($request);
        } catch (\Throwable $e) {
            $this->logger->warning('geocoding_failed', ['reason' => 'network_error', 'exception' => $e]);

            return null;
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->warning('geocoding_failed', ['reason' => 'http_error', 'status' => $response->getStatusCode()]);

            return null;
        }

        $data = json_decode((string) $response->getBody(), true);
        if (!is_array($data)) {
            $this->logger->warning('geocoding_failed', ['reason' => 'malformed_response']);

            return null;
        }

        $first = $data[0] ?? null;
        if (!is_array($first) || !isset($first['lat'], $first['lon'])) {
            $this->logger->warning('geocoding_failed', ['reason' => 'malformed_response']);

            return null;
        }

        $lat = $first['lat'];
        $lon = $first['lon'];
        if (!is_string($lat) || !is_string($lon)) {
            $this->logger->warning('geocoding_failed', ['reason' => 'malformed_response']);

            return null;
        }

        $displayName = isset($first['display_name']) && is_string($first['display_name'])
            ? $first['display_name']
            : '';

        $this->logger->info('geocoding_succeeded', [
            'latitude' => (float) $lat,
            'longitude' => (float) $lon,
        ]);

        return new GeoLocation(
            latitude: (float) $lat,
            longitude: (float) $lon,
            displayName: $displayName,
        );
    }
}
