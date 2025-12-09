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

    // public function test_throws_exception_when_adding_invalid_product(): void
    // {
    //     $this->catalogue->method('hasProduct')->willReturn(false);
        
    //     $basket = new Basket($this->catalogue, $this->deliveryRule);

    //     $this->expectException(\InvalidArgumentException::class);
    //     $basket->add('INVALID');
    // }

    // public function test_calculates_total_with_single_product(): void
    // {
    //     $product = new Product('R01', 'Red Widget', 32.95);
        
    //     $this->catalogue->method('hasProduct')->willReturn(true);
    //     $this->catalogue->method('getProduct')->willReturn($product);
    //     $this->deliveryRule->method('calculate')->willReturn(4.95);

    //     $basket = new Basket($this->catalogue, $this->deliveryRule);
    //     $basket->add('R01');

    //     $this->assertEquals(37.90, $basket->total());
    // }

    // public function test_applies_offers_when_calculating_total(): void
    // {
    //     $product = new Product('R01', 'Red Widget', 32.95);
        
    //     $this->catalogue->method('hasProduct')->willReturn(true);
    //     $this->catalogue->method('getProduct')->willReturn($product);
    //     $this->deliveryRule->method('calculate')->willReturn(0.0);

    //     $offer = $this->createMock(OfferInterface::class);
    //     $offer->method('apply')
    //         ->willReturnCallback(function ($totals) {
    //             $totals['R01'] = 50.00; // Apply discount
    //             return $totals;
    //         });

    //     $basket = new Basket($this->catalogue, $this->deliveryRule, [$offer]);
    //     $basket->add('R01');

    //     $this->assertEquals(50.00, $basket->total());
    // }
}
