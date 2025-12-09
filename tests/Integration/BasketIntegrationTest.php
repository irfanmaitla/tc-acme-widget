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

    public function test_basket_example_2(): void
    {
        // R01, R01
        $offers = [new BuyOneGetOneHalfPrice('R01')];
        $basket = new Basket($this->catalogue, $this->deliveryRule, $offers);
        
        $basket->add('R01');
        $basket->add('R01');

        $this->assertEquals(54.37, $basket->total());
    }

    public function test_basket_example_3(): void
    {
        // R01, G01
        $offers = [new BuyOneGetOneHalfPrice('R01')];
        $basket = new Basket($this->catalogue, $this->deliveryRule, $offers);
        
        $basket->add('R01');
        $basket->add('G01');

        $this->assertEquals(60.85, $basket->total());
    }

    public function test_basket_example_4(): void
    {
        // B01, B01, R01, R01, R01
        $offers = [new BuyOneGetOneHalfPrice('R01')];
        $basket = new Basket($this->catalogue, $this->deliveryRule, $offers);
        
        $basket->add('B01');
        $basket->add('B01');
        $basket->add('R01');
        $basket->add('R01');
        $basket->add('R01');

        $this->assertEquals(98.27, $basket->total());
    }

    public function test_delivery_cost_under_50(): void
    {
        $basket = new Basket($this->catalogue, $this->deliveryRule);
        $basket->add('B01'); // $7.95

        // Expected: $7.95 + $4.95 = $12.90
        $this->assertEquals(12.90, $basket->total());
    }

    public function test_delivery_cost_between_50_and_90(): void
    {
        $basket = new Basket($this->catalogue, $this->deliveryRule);
        $basket->add('R01'); // $32.95
        $basket->add('G01'); // $24.95

        // Expected: $57.90 + $2.95 = $60.85
        $this->assertEquals(60.85, $basket->total());
    }

    // public function test_delivery_cost_over_90(): void
    // {
    //     $basket = new Basket($this->catalogue, $this->deliveryRule);
    //     $basket->add('R01'); // $32.95
    //     $basket->add('R01'); // $32.95
    //     $basket->add('R01'); // $32.95

    //     // Expected: $98.85 + $0 = $98.85
    //     $this->assertEquals(98.85, $basket->total());
    // }

    // public function test_buy_one_get_one_half_price_with_two_items(): void
    // {
    //     $offers = [new BuyOneGetOneHalfPrice('R01')];
    //     $basket = new Basket($this->catalogue, $this->deliveryRule, $offers);
        
    //     $basket->add('R01');
    //     $basket->add('R01');

    //     // Expected: $32.95 + $16.475 + delivery
    //     // Subtotal: $49.425 -> delivery $4.95
    //     // Total: $54.37 (rounded)
    //     $this->assertEquals(54.37, $basket->total());
    // }

    // public function test_buy_one_get_one_half_price_with_three_items(): void
    // {
    //     $offers = [new BuyOneGetOneHalfPrice('R01')];
    //     $basket = new Basket($this->catalogue, $this->deliveryRule, $offers);
        
    //     $basket->add('R01');
    //     $basket->add('R01');
    //     $basket->add('R01');

    //     // Expected: $32.95 + $16.475 + $32.95 + delivery
    //     // Subtotal: $82.375 -> delivery $2.95
    //     // Total: $85.32 (rounded)
    //     $this->assertEquals(85.32, $basket->total());
    // }
}
