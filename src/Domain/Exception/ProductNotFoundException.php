<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ProductNotFoundException extends \RuntimeException
{
    public function __construct(string $sku)
    {
        parent::__construct(sprintf('Product "%s" not found', $sku));
    }
}
