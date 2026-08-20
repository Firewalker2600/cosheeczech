<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class OrderNotFoundException extends \RuntimeException
{
    public function __construct(string $orderId)
    {
        parent::__construct(sprintf('Order "%s" not found', $orderId));
    }
}
