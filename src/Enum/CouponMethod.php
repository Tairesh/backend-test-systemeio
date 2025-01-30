<?php

declare(strict_types=1);

namespace App\Enum;

enum CouponMethod: int
{
    case Fixed = 1;
    case Percent = 2;
}
