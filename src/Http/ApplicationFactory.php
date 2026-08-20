<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Controller\CartController;
use App\Http\Controller\OrderController;
use App\Http\Middleware\ErrorHandlingMiddleware;
use App\Http\Middleware\RequestLoggingMiddleware;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

final class ApplicationFactory
{
    /**
     * @return App<ContainerInterface>
     */
    public static function create(ContainerInterface $container): App
    {
        $app = AppFactory::createFromContainer($container);

        // Routing + body parsing are not added by AppFactory::create(); add them
        // explicitly. Body parsing is added after routing so it wraps the route
        // handler (LIFO middleware order).
        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();

        $app->post('/api/cart', [CartController::class, 'create']);
        $app->get('/api/cart/{id}', [CartController::class, 'get']);
        $app->post('/api/cart/add', [CartController::class, 'addItem']);
        $app->post('/api/cart/remove', [CartController::class, 'removeItem']);
        $app->post('/api/orders', [OrderController::class, 'create']);
        $app->get('/api/orders', [OrderController::class, 'getAll']);
        $app->get('/api/orders/{id}', [OrderController::class, 'get']);

        // Map domain exceptions to JSON error responses.
        $errorMiddleware = $container->get(ErrorHandlingMiddleware::class);
        assert($errorMiddleware instanceof ErrorHandlingMiddleware);
        $app->add($errorMiddleware);

        // Outermost: one access-log line + request id per request.
        $requestLoggingMiddleware = $container->get(RequestLoggingMiddleware::class);
        assert($requestLoggingMiddleware instanceof RequestLoggingMiddleware);
        $app->add($requestLoggingMiddleware);

        return $app;
    }
}
