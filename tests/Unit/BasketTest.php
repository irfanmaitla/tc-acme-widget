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

    public function test_can_add_product_to_basket(): void
    {
        $this->catalogue->method('hasProduct')->willReturn(true);
        
        $basket = new Basket($this->catalogue, $this->deliveryRule);
        $basket->add('R01');

        $this->assertCount(1, $basket->getItems());
        $this->assertEquals(['R01'], $basket->getItems());
    }
}
