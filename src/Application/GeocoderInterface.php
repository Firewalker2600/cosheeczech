<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Model\GeoLocation;

interface GeocoderInterface
{
    /**
     * Resolve a shipping address to a geographic location.
     * Returns null when the address cannot be geocoded (never throws on network error).
     */
    public function geocode(string $address): ?GeoLocation;
}
