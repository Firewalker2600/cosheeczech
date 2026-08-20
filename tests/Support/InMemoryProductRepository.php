<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Model\Product;
use App\Domain\Model\Sku;
use App\Domain\Repository\ProductRepositoryInterface;

final class InMemoryProductRepository implements ProductRepositoryInterface
{
    /** @var array<string, Product> */
    private array $products = [];

    public function find(Sku $sku): ?Product
    {
        return $this->products[$sku->value] ?? null;
    }

    public function save(Product $product): void
    {
        $this->products[$product->sku->value] = $product;
    }
}
