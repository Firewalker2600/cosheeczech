<?php

declare(strict_types=1);

namespace App\Infrastructure\Geocoding;

use App\Application\GeocoderInterface;
use App\Domain\Model\GeoLocation;

/**
 * No-op geocoder for offline/test environments. Always returns null,
 * so orders are created without a geo location.
 */
final readonly class NoopGeocoder implements GeocoderInterface
{
    public function geocode(string $address): ?GeoLocation
    {
        return null;
    }
}
