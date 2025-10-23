<?php

declare(strict_types=1);

namespace AcmeWidgetCo\DeliveryRules;

class AcmeDeliveryRule extends TieredDeliveryRule
{
    public function __construct()
    {
        parent::__construct([
            ['threshold' => 90.00, 'cost' => 0.00],
            ['threshold' => 50.00, 'cost' => 2.95],
            ['threshold' => 0.00, 'cost' => 4.95],
        ]);
    }
}
