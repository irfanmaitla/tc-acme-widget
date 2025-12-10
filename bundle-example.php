#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use AcmeWidgetCo\Bundles\ProductBundle;
use AcmeWidgetCo\Bundles\BundleManager;
use AcmeWidgetCo\Models\Product;

echo "=== Product Bundle Feature Demo ===\n\n";

// Create products
$products = [
    new Product('R01', 'Red Widget', 32.95),
    new Product('G01', 'Green Widget', 24.95),
    new Product('B01', 'Blue Widget', 7.95),
];

// Create bundles
$starterBundle = new ProductBundle(
    'BUNDLE01',
    'Widget Starter Pack',
    ['R01', 'G01', 'B01'],
    55.00  // Save $10.85 ($65.85 - $55.00)
);

$twoRedBundle = new ProductBundle(
    'BUNDLE02',
    'Red Widget Pair',
    ['R01', 'R01'],
    60.00  // Save $5.90 ($65.90 - $60.00)
);

// Create bundle manager
$bundleManager = new BundleManager([$starterBundle, $twoRedBundle]);

echo "Available Bundles:\n";
foreach ($bundleManager->getAllBundles() as $bundle) {
    echo "  - {$bundle->getName()} ({$bundle->getCode()}): $" . 
         number_format($bundle->getBundlePrice(), 2) . "\n";
    echo "    Contains: " . implode(', ', $bundle->getProductCodes()) . "\n";
    $savings = $bundle->calculateSavings($products);
    echo "    Save: $" . number_format($savings, 2) . "\n\n";
}

// Example 1: Basket with bundle items
echo "Example 1: Items in basket: R01, G01, B01\n";
$items1 = ['R01', 'G01', 'B01'];
$result1 = $bundleManager->calculateWithBundles($items1, $products);
echo "Total with bundle: $" . number_format($result1['total'], 2) . "\n";
echo "Bundles applied: " . count($result1['bundlesApplied']) . "\n";
echo "Total savings: $" . number_format($result1['savings'], 2) . "\n\n";

// Example 2: Basket with partial bundle
echo "Example 2: Items in basket: R01, G01\n";
$items2 = ['R01', 'G01'];
$result2 = $bundleManager->calculateWithBundles($items2, $products);
echo "Total: $" . number_format($result2['total'], 2) . "\n";
echo "Bundles applied: " . count($result2['bundlesApplied']) . "\n";
echo "Note: No bundle applied (missing B01 for starter pack)\n\n";

// Example 3: Multiple bundle applications
echo "Example 3: Items in basket: R01, R01, G01, B01\n";
$items3 = ['R01', 'R01', 'G01', 'B01'];
$result3 = $bundleManager->calculateWithBundles($items3, $products);
echo "Total with bundle: $" . number_format($result3['total'], 2) . "\n";
echo "Bundles applied: " . count($result3['bundlesApplied']) . "\n";
if (!empty($result3['bundlesApplied'])) {
    foreach ($result3['bundlesApplied'] as $applied) {
        echo "  - {$applied->getName()}\n";
    }
}
echo "Total savings: $" . number_format($result3['savings'], 2) . "\n";

echo "\n=== Demo Complete ===\n";