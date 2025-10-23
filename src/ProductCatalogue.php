<?php

declare(strict_types=1);

namespace AcmeWidgetCo;

use AcmeWidgetCo\Contracts\ProductCatalogueInterface;
use AcmeWidgetCo\Models\Product;

class ProductCatalogue implements ProductCatalogueInterface
{
    /** @var array<string, Product> */
    private array $products = [];

    /**
     * @param array<Product> $products
     */
    public function __construct(array $products = [])
    {
        foreach ($products as $product) {
            $this->products[$product->getCode()] = $product;
        }
    }

    public function getProduct(string $code): Product
    {
        if (!$this->hasProduct($code)) {
            throw new \InvalidArgumentException("Product {$code} not found");
        }

        return $this->products[$code];
    }

    public function hasProduct(string $code): bool
    {
        return isset($this->products[$code]);
    }
}
