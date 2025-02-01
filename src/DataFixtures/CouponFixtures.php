<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Coupon;
use App\Enum\CouponMethod;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CouponFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $coupons = [
            ['code' => 'P6', 'method' => CouponMethod::Percent, 'value' => 6],
            ['code' => 'P10', 'method' => CouponMethod::Percent, 'value' => 10],
            ['code' => 'P100', 'method' => CouponMethod::Percent, 'value' => 100],
            ['code' => 'F10', 'method' => CouponMethod::Fixed, 'value' => 1000],
        ];

        foreach ($coupons as $couponData) {
            $coupon = new Coupon();
            $coupon->setCode($couponData['code']);
            $coupon->setMethod($couponData['method']);
            $coupon->setValue($couponData['value']);
            $manager->persist($coupon);
        }

        $manager->flush();
    }
}