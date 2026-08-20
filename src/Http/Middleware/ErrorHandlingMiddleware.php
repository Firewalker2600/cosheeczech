<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Exception\CartItemNotFoundException;
use App\Domain\Exception\CartNotFoundException;
use App\Domain\Exception\EmptyCartException;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Exception\ProductNotFoundException;
use App\Http\Json;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;

/**
 * Maps domain exceptions to JSON error responses, and is the final 500 fallback.
 */
final readonly class ErrorHandlingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        private Json $json,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpNotFoundException $e) {
            return $this->error(404, 'Not found');
        } catch (HttpMethodNotAllowedException $e) {
            return $this->error(405, 'Method not allowed');
        } catch (ProductNotFoundException|CartNotFoundException|OrderNotFoundException|CartItemNotFoundException $e) {
            return $this->error(404, $e->getMessage());
        } catch (EmptyCartException $e) {
            return $this->error(400, $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->error(400, $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('unhandled_exception', ['exception' => $e]);

            return $this->error(500, 'Internal server error');
        }
    }

    private function error(int $status, string $message): ResponseInterface
    {
        return $this->json->write(
            $this->responseFactory->createResponse($status),
            ['error' => $message],
            $status,
        );
    }
}
