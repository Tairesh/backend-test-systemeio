<?php

namespace App\Service;

class TaxRateService
{
    /**
     * Get tax rate by tax number
     *
     * @param string $taxNumber
     * @return float
     * @throws \InvalidArgumentException
     */
    public function getTaxRate(string $taxNumber): float
    {
        $countryCode = substr($taxNumber, 0, 2);
        return match ($countryCode) {
            'DE' => 0.19,
            'IT' => 0.22,
            'FR' => 0.2,
            'GR' => 0.24,
            default => throw new \InvalidArgumentException('Invalid country code'),
        };
    }
}