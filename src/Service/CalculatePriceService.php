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
    private ProductRepository $productRepository;
    private CouponRepository $couponRepository;

    public function __construct(ProductRepository $productRepository, CouponRepository $couponRepository)
    {
        $this->productRepository = $productRepository;
        $this->couponRepository = $couponRepository;
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

        return (int) round($price);
    }

    /**
     * Return tax rate based on the country code
     *
     * @param string $taxNumber
     * @return float
     */
    private function getTaxRate(string $taxNumber): float
    {
        if (str_starts_with($taxNumber, 'DE')) {
            return 0.19;
        } elseif (str_starts_with($taxNumber, 'IT')) {
            return 0.22;
        } elseif (str_starts_with($taxNumber, 'FR')) {
            return 0.20;
        } elseif (str_starts_with($taxNumber, 'GR')) {
            return 0.24;
        }

        throw new \InvalidArgumentException('Invalid tax number');
    }

    private function applyTaxRate(float$price, string $taxNumber): float
    {
        $taxRate = $this->getTaxRate($taxNumber);
        return $price + ($price * $taxRate);
    }

    private function applyCoupon(float $price, Coupon $coupon): float
    {
        if ($coupon->getMethod() === CouponMethod::Fixed) {
            return max(0, $price - $coupon->getValue());
        } elseif ($coupon->getMethod() === CouponMethod::Percent) {
            return $price - ($price * ($coupon->getValue() / 100));
        }

        return $price;
    }
}
