<?php

declare(strict_types=1);

namespace AcmeWidgetCo\Tests\Integration;

use AcmeWidgetCo\Basket;
use AcmeWidgetCo\DeliveryRules\AcmeDeliveryRule;
use AcmeWidgetCo\Models\Product;
use AcmeWidgetCo\Offers\BuyOneGetOneHalfPrice;
use AcmeWidgetCo\ProductCatalogue;
use PHPUnit\Framework\TestCase;

class BasketIntegrationTest extends TestCase
{
    private ProductCatalogue $catalogue;
    private AcmeDeliveryRule $deliveryRule;

    protected function setUp(): void
    {
        $this->catalogue = new ProductCatalogue([
            new Product('R01', 'Red Widget', 32.95),
            new Product('G01', 'Green Widget', 24.95),
            new Product('B01', 'Blue Widget', 7.95),
        ]);

        $this->deliveryRule = new AcmeDeliveryRule();
    }

    public function test_basket_example_1(): void
    {
        // B01, G01
        $offers = [new BuyOneGetOneHalfPrice('R01')];
        $basket = new Basket($this->catalogue, $this->deliveryRule, $offers);
        
        $basket->add('B01');
        $basket->add('G01');

        $this->assertEquals(37.85, $basket->total());
    }
}
