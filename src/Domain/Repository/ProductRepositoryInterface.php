<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Product;
use App\Domain\Model\Sku;

interface ProductRepositoryInterface
{
    public function find(Sku $sku): ?Product;

    public function save(Product $product): void;
}
