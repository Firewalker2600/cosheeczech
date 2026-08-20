<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Result of geocoding a shipping address.
 */
final readonly class GeoLocation
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public string $displayName,
    ) {
    }

    public function toString(): string
    {
        if ($this->displayName !== '') {
            return sprintf('%s (%s)', $this->coordinates(), $this->displayName);
        }

        return $this->coordinates();
    }

    private function coordinates(): string
    {
        return sprintf('%f, %f', $this->latitude, $this->longitude);
    }
}
