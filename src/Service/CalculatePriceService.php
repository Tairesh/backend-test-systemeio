<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\BasePriceRequest;
use App\Entity\Coupon;
use App\Enum\CouponMethod;
use App\Repository\ProductRepository;
use App\Repository\CouponRepository;

class CalculatePriceService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CouponRepository  $couponRepository,
        private readonly TaxRateService    $taxRateService,
    )
    {
    }

    /**
     * @param BasePriceRequest $request
     * @return int
     * @throws \InvalidArgumentException
     */
    public function calculatePrice(BasePriceRequest $request): int
    {
        $product = $this->productRepository->find($request->product);
        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        $price = $product->getPrice();
        if ($request->couponCode) {
            $coupon = $this->couponRepository->findOneByCode($request->couponCode);
            if ($coupon) {
                $price = $this->applyCoupon($price, $coupon);
            }
        }
        $price = $this->applyTaxRate($price, $request->taxNumber);

        return (int)round($price);
    }

    private function applyTaxRate(float $price, string $taxNumber): float
    {
        $taxRate = $this->taxRateService->getTaxRate($taxNumber);
        return $price + ($price * $taxRate);
    }

    private function applyCoupon(float $price, Coupon $coupon): float
    {
        $price = match ($coupon->getMethod()) {
            CouponMethod::Fixed => $price - $coupon->getValue(),
            CouponMethod::Percent => $price - ($price * ($coupon->getValue() / 100)),
            default => throw new \InvalidArgumentException('Invalid coupon method'),
        };

        return max(0, $price);
    }
}
