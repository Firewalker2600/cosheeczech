<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class EmptyCartException extends \RuntimeException
{
    public function __construct(string $cartId)
    {
        parent::__construct(sprintf('Cart "%s" is empty', $cartId));
    }
}
