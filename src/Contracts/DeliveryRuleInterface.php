<?php

declare(strict_types=1);

namespace AcmeWidgetCo\Contracts;

interface DeliveryRuleInterface
{
    public function calculate(float $orderTotal): float;
}
