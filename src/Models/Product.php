<?php

declare(strict_types=1);

namespace AcmeWidgetCo\Models;

class Product
{
    public function __construct(
        private readonly string $code,
        private readonly string $name,
        private readonly float $price
    ) {
        if ($price < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
