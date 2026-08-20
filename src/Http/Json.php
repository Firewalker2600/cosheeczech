<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final readonly class Json
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function write(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            // Encoding fails only on invalid UTF-8 in a value (e.g. a
            // user-supplied shipping address) — fall back to a 500.
            $this->logger->error('json_encode_failed', ['exception' => $e]);
            $body = '{"error":"Internal server error"}';
            $status = 500;
        }

        $response->getBody()->write($body);

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
