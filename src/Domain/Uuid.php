<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * RFC 4122 UUID helpers (no external dependency required).
 */
final class Uuid
{
    public static function v4(): string
    {
        return self::format(self::applyVersionAndVariant(random_bytes(16), 0x40));
    }

    /**
     * RFC 4122 version 5 (SHA-1 name-based) UUID.
     * Deterministic: the same (namespace, name) pair always yields the same id.
     */
    public static function v5(string $namespace, string $name): string
    {
        $hash = sha1(self::toBytes($namespace) . $name, true);
        $bytes = substr($hash, 0, 16);

        return self::format(self::applyVersionAndVariant($bytes, 0x50));
    }

    /** @param string $bytes 16 raw bytes */
    private static function applyVersionAndVariant(string $bytes, int $version): string
    {
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | $version);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return $bytes;
    }

    /** @param string $bytes 16 raw bytes */
    private static function format(string $bytes): string
    {
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /** @return string 16 raw bytes */
    private static function toBytes(string $uuid): string
    {
        $binary = hex2bin(str_replace('-', '', $uuid));
        if ($binary === false) {
            throw new \InvalidArgumentException(sprintf('Invalid UUID "%s"', $uuid));
        }

        return $binary;
    }
}
