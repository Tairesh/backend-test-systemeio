<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\CalculatePriceRequest;
use App\Entity\Coupon;
use App\Entity\Product;
use App\Enum\CouponMethod;
use App\Repository\ProductRepository;
use App\Repository\CouponRepository;
use App\Service\CalculatePriceService;
use App\Service\TaxRateService;
use PHPUnit\Framework\TestCase;

class CalculatePriceServiceTest extends TestCase
{
    private $productRepository;
    private $couponRepository;
    private $taxRateService;
    private $calculatePriceService;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->couponRepository = $this->createMock(CouponRepository::class);
        $this->taxRateService = $this->createMock(TaxRateService::class);
        $this->calculatePriceService = new CalculatePriceService($this->productRepository, $this->couponRepository, $this->taxRateService);
    }

    public function testCalculatePriceWithoutCoupon()
    {
        $product = new Product();
        $product->setPrice(10000);

        $this->productRepository->method('find')->willReturn($product);
        $this->taxRateService->method('getTaxRate')->willReturn(0.19);

        $request = new CalculatePriceRequest(1, 'DE123456789', null);

        $price = $this->calculatePriceService->calculatePrice($request);

        $this->assertEquals(11900, $price); // 100 + 19% tax
    }

    public function testCalculatePriceWithFixedCoupon()
    {
        $product = new Product();
        $product->setPrice(10000);

        $coupon = new Coupon();
        $coupon->setMethod(CouponMethod::Fixed);
        $coupon->setValue(1000);

        $this->productRepository->method('find')->willReturn($product);
        $this->couponRepository->method('findOneByCode')->willReturn($coupon);
        $this->taxRateService->method('getTaxRate')->willReturn(0.19);

        $request = new CalculatePriceRequest(1, 'DE123456789', 'FIXED10');

        $price = $this->calculatePriceService->calculatePrice($request);

        $this->assertEquals(10710, $price); // 100 - 10 fixed discount + 19% tax
    }

    public function testCalculatePriceWithPercentCoupon()
    {
        $product = new Product();
        $product->setPrice(10000);

        $coupon = new Coupon();
        $coupon->setMethod(CouponMethod::Percent);
        $coupon->setValue(6);

        $this->productRepository->method('find')->willReturn($product);
        $this->couponRepository->method('findOneByCode')->willReturn($coupon);
        $this->taxRateService->method('getTaxRate')->willReturn(0.24);

        $request = new CalculatePriceRequest(1, 'GR123456789', 'P6');

        $price = $this->calculatePriceService->calculatePrice($request);

        $this->assertEquals(11656, $price); // 100 - 6% coupon + 24% tax).
    }

    public function testCalculatePriceWithInvalidProduct()
    {
        $this->productRepository->method('find')->willReturn(null);

        $request = new CalculatePriceRequest(999, 'DE123456789', null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product not found');

        $this->calculatePriceService->calculatePrice($request);
    }
}
