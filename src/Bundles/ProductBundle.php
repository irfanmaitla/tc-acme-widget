<?php

declare(strict_types=1);

namespace AcmeWidgetCo\Bundles;

use AcmeWidgetCo\Models\Product;

/**
 * Represents a product bundle with a discounted price
 * 
 * Example: "Widget Starter Pack" - R01 + G01 + B01 for $55
 */
class ProductBundle
{
    /** @var array<string> */
    private array $productCodes;

    /**
     * @param string $code Bundle identifier (e.g., "BUNDLE01")
     * @param string $name Bundle name
     * @param array<string> $productCodes Product codes in the bundle
     * @param float $bundlePrice Special bundle price
     */
    public function __construct(
        private readonly string $code,
        private readonly string $name,
        array $productCodes,
        private readonly float $bundlePrice
    ) {
        if (empty($productCodes)) {
            throw new \InvalidArgumentException('Bundle must contain at least one product');
        }

        if ($bundlePrice < 0) {
            throw new \InvalidArgumentException('Bundle price cannot be negative');
        }

        $this->productCodes = $productCodes;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string>
     */
    public function getProductCodes(): array
    {
        return $this->productCodes;
    }

    public function getBundlePrice(): float
    {
        return $this->bundlePrice;
    }

    /**
     * Check if the given items contain all products needed for this bundle
     * 
     * @param array<string> $items
     * @return bool
     */
    public function canApplyToItems(array $items): bool
    {
        $itemCounts = array_count_values($items);
        
        foreach ($this->productCodes as $productCode) {
            if (!isset($itemCounts[$productCode]) || $itemCounts[$productCode] < 1) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Calculate savings compared to individual product prices
     * 
     * @param array<Product> $products
     * @return float
     */
    public function calculateSavings(array $products): float
    {
        $totalRegularPrice = 0.0;
        
        foreach ($this->productCodes as $productCode) {
            foreach ($products as $product) {
                if ($product->getCode() === $productCode) {
                    $totalRegularPrice += $product->getPrice();
                    break;
                }
            }
        }
        
        $savings = $totalRegularPrice - $this->bundlePrice;
        return max(0.0, $savings);
    }

    /**
     * Get the number of times this bundle can be applied to the given items
     * 
     * @param array<string> $items
     * @return int
     */
    public function getMaxApplications(array $items): int
    {
        if (!$this->canApplyToItems($items)) {
            return 0;
        }

        $itemCounts = array_count_values($items);
        $maxApplications = PHP_INT_MAX;

        foreach ($this->productCodes as $productCode) {
            $availableCount = $itemCounts[$productCode] ?? 0;
            $requiredCount = 1; // Each bundle needs 1 of each product
            
            $possibleApplications = intdiv($availableCount, $requiredCount);
            $maxApplications = min($maxApplications, $possibleApplications);
        }

        return $maxApplications;
    }
}