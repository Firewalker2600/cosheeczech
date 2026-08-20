<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class CartItemNotFoundException extends \RuntimeException
{
    public function __construct(string $sku)
    {
        parent::__construct(sprintf('Product "%s" is not in the cart', $sku));
    }
}
