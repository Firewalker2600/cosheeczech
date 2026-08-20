<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

/**
 * Type-safe accessors for SQLite rows.
 *
 * PDO returns rows as array<string, mixed>; these helpers narrow each column to
 * its declared type and fail loudly on schema drift instead of casting blindly.
 */
final class Row
{
    /**
     * @param array<string, mixed> $row
     */
    public static function string(array $row, string $key): string
    {
        $value = $row[$key] ?? null;
        if (!is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Column "%s" must be a string', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function nullableString(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Column "%s" must be a string or null', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function int(array $row, string $key): int
    {
        $value = $row[$key] ?? null;
        if (!is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Column "%s" must be an integer', $key));
        }

        return $value;
    }
}
