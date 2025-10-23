<?php

declare(strict_types=1);

namespace AcmeWidgetCo\Contracts;

use AcmeWidgetCo\Models\Product;

interface ProductCatalogueInterface
{
    public function getProduct(string $code): Product;
    public function hasProduct(string $code): bool;
}
