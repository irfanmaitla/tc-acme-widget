<?php

declare(strict_types=1);

namespace AcmeWidgetCo;

use AcmeWidgetCo\Contracts\DeliveryRuleInterface;
use AcmeWidgetCo\Contracts\OfferInterface;
use AcmeWidgetCo\Contracts\ProductCatalogueInterface;

class Basket
{
    /** @var array<string> */
    private array $items = [];

    /**
     * @param ProductCatalogueInterface $catalogue
     * @param DeliveryRuleInterface $deliveryRule
     * @param array<OfferInterface> $offers
     */
    public function __construct(
        private readonly ProductCatalogueInterface $catalogue,
        private readonly DeliveryRuleInterface $deliveryRule,
        private readonly array $offers = []
    ) {
    }

    public function add(string $productCode): void
    {
        if (!$this->catalogue->hasProduct($productCode)) {
            throw new \InvalidArgumentException("Product {$productCode} does not exist");
        }

        $this->items[] = $productCode;
    }

    public function total(): float
    {
        $subtotal = $this->calculateSubtotal();
        $deliveryCost = $this->deliveryRule->calculate($subtotal);

        return round($subtotal + $deliveryCost, 2);
    }

    private function calculateSubtotal(): float
    {
        $productTotals = $this->calculateProductTotals();
        
        foreach ($this->offers as $offer) {
            $productTotals = $offer->apply($productTotals, $this->items);
        }

        return round(array_sum($productTotals), 2);
    }

    /**
     * @return array<string, float>
     */
    private function calculateProductTotals(): array
    {
        $totals = [];

        foreach ($this->items as $productCode) {
            $product = $this->catalogue->getProduct($productCode);
            
            if (!isset($totals[$productCode])) {
                $totals[$productCode] = 0.0;
            }
            
            $totals[$productCode] += $product->getPrice();
        }

        return $totals;
    }

    /**
     * @return array<string>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
