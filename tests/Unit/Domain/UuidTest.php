<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Uuid;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    private const DNS_NAMESPACE = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    #[Test]
    public function generatesRfc4122V4FormattedUuid(): void
    {
        $uuid = Uuid::v4();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    #[Test]
    public function generatesUniqueV4Values(): void
    {
        self::assertNotSame(Uuid::v4(), Uuid::v4());
    }

    #[Test]
    public function v5MatchesTheRfc4122TestVector(): void
    {
        self::assertSame(
            '2ed6657d-e927-568b-95e1-2665a8aea6a2',
            Uuid::v5(self::DNS_NAMESPACE, 'www.example.com'),
        );
    }

    #[Test]
    public function v5ProducesAnRfc4122V5FormattedUuid(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            Uuid::v5(self::DNS_NAMESPACE, 'SOAP-LAVENDER'),
        );
    }

    #[Test]
    public function v5IsDeterministicForTheSameName(): void
    {
        self::assertSame(
            Uuid::v5(self::DNS_NAMESPACE, 'SOAP-LAVENDER'),
            Uuid::v5(self::DNS_NAMESPACE, 'SOAP-LAVENDER'),
        );
    }

    #[Test]
    public function v5DiffersAcrossNames(): void
    {
        self::assertNotSame(
            Uuid::v5(self::DNS_NAMESPACE, 'SOAP-LAVENDER'),
            Uuid::v5(self::DNS_NAMESPACE, 'SOAP-HONEY'),
        );
    }
}
