#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use AcmeWidgetCo\Basket;
use AcmeWidgetCo\DeliveryRules\AcmeDeliveryRule;
use AcmeWidgetCo\Models\Product;
use AcmeWidgetCo\Offers\BuyOneGetOneHalfPrice;
use AcmeWidgetCo\ProductCatalogue;

// Initialize the product catalogue
$catalogue = new ProductCatalogue([
    new Product('R01', 'Red Widget', 32.95),
    new Product('G01', 'Green Widget', 24.95),
    new Product('B01', 'Blue Widget', 7.95),
]);

// Initialize delivery rules
$deliveryRule = new AcmeDeliveryRule();

// Initialize offers
$offers = [
    new BuyOneGetOneHalfPrice('R01'),
];

// Create basket
$basket = new Basket($catalogue, $deliveryRule, $offers);

// Example 1: B01, G01
echo "Example 1: B01, G01\n";
$basket->add('B01');
$basket->add('G01');
echo "Total: $" . number_format($basket->total(), 2) . "\n\n";

// Example 2: R01, R01
echo "Example 2: R01, R01\n";
$basket2 = new Basket($catalogue, $deliveryRule, $offers);
$basket2->add('R01');
$basket2->add('R01');
echo "Total: $" . number_format($basket2->total(), 2) . "\n\n";

// Example 3: R01, G01
echo "Example 3: R01, G01\n";
$basket3 = new Basket($catalogue, $deliveryRule, $offers);
$basket3->add('R01');
$basket3->add('G01');
echo "Total: $" . number_format($basket3->total(), 2) . "\n\n";

// Example 4: B01, B01, R01, R01, R01
echo "Example 4: B01, B01, R01, R01, R01\n";
$basket4 = new Basket($catalogue, $deliveryRule, $offers);
$basket4->add('B01');
$basket4->add('B01');
$basket4->add('R01');
$basket4->add('R01');
$basket4->add('R01');
echo "Total: $" . number_format($basket4->total(), 2) . "\n";
