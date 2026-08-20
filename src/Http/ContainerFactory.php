<?php

declare(strict_types=1);

namespace App\Http;

use App\Application\GeocoderInterface;
use App\Domain\Repository\CartRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\ProductRepositoryInterface;
use App\Infrastructure\Geocoding\NoopGeocoder;
use App\Infrastructure\Geocoding\NominatimGeocoder;
use App\Infrastructure\Persistence\ConnectionFactory;
use App\Infrastructure\Persistence\Schema;
use App\Infrastructure\Persistence\SqliteCartRepository;
use App\Infrastructure\Persistence\SqliteOrderRepository;
use App\Infrastructure\Persistence\SqliteProductRepository;
use DI\Container;
use DI\ContainerBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

final class ContainerFactory
{
    /**
     * @param array{
     *     dsn: string,
     *     geocoder?: 'nominatim'|'noop',
     *     geocoder_user_agent?: string,
     *     logger?: LoggerInterface,
     * } $config
     */
    public static function create(array $config): Container
    {
        $pdo = null;

        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->addDefinitions([
            \PDO::class => static function () use (&$pdo, $config): \PDO {
                if ($pdo === null) {
                    $pdo = (new ConnectionFactory($config['dsn']))->create();
                    Schema::create($pdo);
                    Schema::seed($pdo);
                }

                return $pdo;
            },

            LoggerInterface::class => static function () use ($config): LoggerInterface {
                if (isset($config['logger'])) {
                    return $config['logger'];
                }

                $handler = new StreamHandler('php://stderr', Logger::INFO);
                $handler->setFormatter(new JsonFormatter());

                return new Logger('cosheeczech', [$handler]);
            },

            ProductRepositoryInterface::class => \DI\autowire(SqliteProductRepository::class),
            CartRepositoryInterface::class => \DI\autowire(SqliteCartRepository::class),
            OrderRepositoryInterface::class => \DI\autowire(SqliteOrderRepository::class),

            GeocoderInterface::class => static function (Container $c) use ($config): GeocoderInterface {
                if (($config['geocoder'] ?? 'noop') === 'nominatim') {
                    $client = $c->get(ClientInterface::class);
                    assert($client instanceof ClientInterface);

                    $requestFactory = $c->get(RequestFactoryInterface::class);
                    assert($requestFactory instanceof RequestFactoryInterface);

                    $logger = $c->get(LoggerInterface::class);
                    assert($logger instanceof LoggerInterface);

                    return new NominatimGeocoder(
                        client: $client,
                        requestFactory: $requestFactory,
                        logger: $logger,
                        userAgent: $config['geocoder_user_agent'] ?? 'cosheeczech/1.0',
                    );
                }

                return new NoopGeocoder();
            },

            ClientInterface::class => \DI\autowire(Client::class),
            RequestFactoryInterface::class => \DI\autowire(HttpFactory::class),
            ResponseFactoryInterface::class => \DI\autowire(HttpFactory::class),
        ]);

        return $builder->build();
    }
}
