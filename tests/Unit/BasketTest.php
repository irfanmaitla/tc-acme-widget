<?php

declare(strict_types=1);

namespace AcmeWidgetCo\Tests\Unit;

use AcmeWidgetCo\Basket;
use AcmeWidgetCo\Contracts\DeliveryRuleInterface;
use AcmeWidgetCo\Contracts\OfferInterface;
use AcmeWidgetCo\Contracts\ProductCatalogueInterface;
use AcmeWidgetCo\Models\Product;
use PHPUnit\Framework\TestCase;

class BasketTest extends TestCase
{
    private ProductCatalogueInterface $catalogue;
    private DeliveryRuleInterface $deliveryRule;

    protected function setUp(): void
    {
        $this->catalogue = $this->createMock(ProductCatalogueInterface::class);
        $this->deliveryRule = $this->createMock(DeliveryRuleInterface::class);
    }
}
