<?php

declare(strict_types=1);

namespace AcmeWidgetCo\Offers;

use AcmeWidgetCo\Contracts\OfferInterface;

class BuyOneGetOneHalfPrice implements OfferInterface
{
    public function __construct(
        private readonly string $productCode
    ) {
    }

    public function apply(array $productTotals, array $items): array
    {
        $count = $this->countProduct($items, $this->productCode);
        
        if ($count < 2) {
            return $productTotals;
        }

        // Calculate how many items get the discount
        $discountedItems = intdiv($count, 2);
        
        // Get the price per item in cents to avoid floating point issues
        $totalCents = round($productTotals[$this->productCode] * 100);
        $pricePerItemCents = round($totalCents / $count);
        
        // Apply discount: half price means divide by 2
        $discountCents = $discountedItems * round($pricePerItemCents / 2);
        
        // Convert back to dollars
        $productTotals[$this->productCode] = ($totalCents - $discountCents) / 100;

        return $productTotals;
    }

    /**
     * @param array<string> $items
     * @param string $productCode
     * @return int
     */
    private function countProduct(array $items, string $productCode): int
    {
        return count(array_filter($items, fn($item) => $item === $productCode));
    }
}
