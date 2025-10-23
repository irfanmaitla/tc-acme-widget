<?php

declare(strict_types=1);

namespace AcmeWidgetCo\Contracts;

interface OfferInterface
{
    /**
     * @param array<string, float> $productTotals
     * @param array<string> $items
     * @return array<string, float>
     */
    public function apply(array $productTotals, array $items): array;
}
