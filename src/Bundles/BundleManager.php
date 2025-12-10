<?php

declare(strict_types=1);

namespace AcmeWidgetCo\Bundles;

use AcmeWidgetCo\Models\Product;
use AcmeWidgetCo\Bundles\ProductBundle;
/**
 * Manages product bundles and applies bundle discounts
 */
class BundleManager
{
    /** @var array<ProductBundle> */
    private array $bundles = [];

    /**
     * @param array<ProductBundle> $bundles
     */
    public function __construct(array $bundles = [])
    {
        foreach ($bundles as $bundle) {
            $this->addBundle($bundle);
        }
    }

    public function addBundle(ProductBundle $bundle): void
    {
        $this->bundles[$bundle->getCode()] = $bundle;
    }

    public function getBundle(string $code): ?ProductBundle
    {
        return $this->bundles[$code] ?? null;
    }

    /**
     * @return array<ProductBundle>
     */
    public function getAllBundles(): array
    {
        return array_values($this->bundles);
    }

    /**
     * Find all applicable bundles for the given items
     * 
     * @param array<string> $items
     * @return array<ProductBundle>
     */
    public function findApplicableBundles(array $items): array
    {
        $applicable = [];

        foreach ($this->bundles as $bundle) {
            if ($bundle->canApplyToItems($items)) {
                $applicable[] = $bundle;
            }
        }

        return $applicable;
    }

    /**
     * Apply the best bundle discount to the items
     * Returns the bundle applied and remaining items
     * 
     * @param array<string> $items
     * @param array<Product> $products
     * @return array{bundle: ProductBundle|null, remainingItems: array<string>, savings: float}
     */
    public function applyBestBundle(array $items, array $products): array
    {
        $applicableBundles = $this->findApplicableBundles($items);

        if (empty($applicableBundles)) {
            return [
                'bundle' => null,
                'remainingItems' => $items,
                'savings' => 0.0
            ];
        }

        // Find the bundle with maximum savings
        $bestBundle = null;
        $maxSavings = 0.0;

        foreach ($applicableBundles as $bundle) {
            $savings = $bundle->calculateSavings($products);
            if ($savings > $maxSavings) {
                $maxSavings = $savings;
                $bestBundle = $bundle;
            }
        }

        if ($bestBundle === null) {
            return [
                'bundle' => null,
                'remainingItems' => $items,
                'savings' => 0.0
            ];
        }

        // Remove bundle items from the basket
        $remainingItems = $items;
        foreach ($bestBundle->getProductCodes() as $productCode) {
            $key = array_search($productCode, $remainingItems, true);
            if ($key !== false) {
                unset($remainingItems[$key]);
            }
        }

        return [
            'bundle' => $bestBundle,
            'remainingItems' => array_values($remainingItems),
            'savings' => $maxSavings
        ];
    }

    /**
     * Calculate total price with bundle discounts applied
     * 
     * @param array<string> $items
     * @param array<Product> $products
     * @return array{total: float, bundlesApplied: array<ProductBundle>, savings: float}
     */
    public function calculateWithBundles(array $items, array $products): array
    {
        $remainingItems = $items;
        $bundlesApplied = [];
        $totalSavings = 0.0;
        $bundleTotal = 0.0;

        // Keep applying bundles while possible
        while (true) {
            $result = $this->applyBestBundle($remainingItems, $products);
            
            if ($result['bundle'] === null) {
                break;
            }

            $bundlesApplied[] = $result['bundle'];
            $bundleTotal += $result['bundle']->getBundlePrice();
            $totalSavings += $result['savings'];
            $remainingItems = $result['remainingItems'];
        }

        // Calculate price for remaining individual items
        $individualTotal = 0.0;
        foreach ($remainingItems as $itemCode) {
            foreach ($products as $product) {
                if ($product->getCode() === $itemCode) {
                    $individualTotal += $product->getPrice();
                    break;
                }
            }
        }

        return [
            'total' => $bundleTotal + $individualTotal,
            'bundlesApplied' => $bundlesApplied,
            'savings' => $totalSavings
        ];
    }

    /**
     * Check if bundle code exists
     */
    public function hasBundle(string $code): bool
    {
        return isset($this->bundles[$code]);
    }

    /**
     * Remove a bundle
     */
    public function removeBundle(string $code): bool
    {
        if (!$this->hasBundle($code)) {
            return false;
        }

        unset($this->bundles[$code]);
        return true;
    }

    /**
     * Get total number of bundles
     */
    public function getBundleCount(): int
    {
        return count($this->bundles);
    }
}