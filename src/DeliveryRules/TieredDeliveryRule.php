<?php

declare(strict_types=1);

namespace AcmeWidgetCo\DeliveryRules;

use AcmeWidgetCo\Contracts\DeliveryRuleInterface;

class TieredDeliveryRule implements DeliveryRuleInterface
{
    /**
     * @param array<array{threshold: float, cost: float}> $tiers
     */
    public function __construct(
        private readonly array $tiers
    ) {
    }

    public function calculate(float $orderTotal): float
    {
        // Sort tiers by threshold in descending order
        $sortedTiers = $this->tiers;
        usort($sortedTiers, fn($a, $b) => $b['threshold'] <=> $a['threshold']);

        foreach ($sortedTiers as $tier) {
            if ($orderTotal >= $tier['threshold']) {
                return $tier['cost'];
            }
        }

        // If no tier matches, return the highest cost (lowest threshold)
        return end($sortedTiers)['cost'] ?? 0.0;
    }
}
