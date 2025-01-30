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
use PHPUnit\Framework\TestCase;

class CalculatePriceServiceTest extends TestCase
{
    private $productRepository;
    private $couponRepository;
    private $calculatePriceService;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->couponRepository = $this->createMock(CouponRepository::class);
        $this->calculatePriceService = new CalculatePriceService($this->productRepository, $this->couponRepository);
    }

    public function testCalculatePriceWithoutCoupon()
    {
        $product = new Product();
        $product->setPrice(100);

        $this->productRepository->method('find')->willReturn($product);

        $request = new CalculatePriceRequest(1, 'DE123456789', null);

        $price = $this->calculatePriceService->calculatePrice($request);

        $this->assertEquals(119, $price); // 100 + 19% tax
    }

    public function testCalculatePriceWithFixedCoupon()
    {
        $product = new Product();
        $product->setPrice(100);

        $coupon = new Coupon();
        $coupon->setMethod(CouponMethod::Fixed);
        $coupon->setValue(10);

        $this->productRepository->method('find')->willReturn($product);
        $this->couponRepository->method('findOneByCode')->willReturn($coupon);

        $request = new CalculatePriceRequest(1, 'DE123456789', 'FIXED10');

        $price = $this->calculatePriceService->calculatePrice($request);

        $this->assertEquals(109, $price); // 100 + 19% tax - 10 fixed discount
    }

    public function testCalculatePriceWithPercentCoupon()
    {
        $product = new Product();
        $product->setPrice(100);

        $coupon = new Coupon();
        $coupon->setMethod(CouponMethod::Percent);
        $coupon->setValue(10);

        $this->productRepository->method('find')->willReturn($product);
        $this->couponRepository->method('findOneByCode')->willReturn($coupon);

        $request = new CalculatePriceRequest(1, 'DE123456789', 'PERCENT10');

        $price = $this->calculatePriceService->calculatePrice($request);

        $this->assertEquals(107, $price); // 100 + 19% tax - 10% discount
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
