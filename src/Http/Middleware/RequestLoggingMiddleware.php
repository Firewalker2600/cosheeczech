<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Uuid;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Emits one access-log line per request and tags the response with a request id.
 */
final readonly class RequestLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = $this->resolveRequestId($request);
        $start = microtime(true);

        $response = $handler->handle($request);

        $this->logger->info('request', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'status' => $response->getStatusCode(),
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ]);

        return $response->withHeader('X-Request-Id', $requestId);
    }

    private function resolveRequestId(ServerRequestInterface $request): string
    {
        $header = trim($request->getHeaderLine('X-Request-Id'));
        if ($header !== '') {
            return $header;
        }

        return Uuid::v4();
    }
}
