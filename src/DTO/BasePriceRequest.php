<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

abstract class BasePriceRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\GreaterThanOrEqual(1)]
        public int $product,

        #[Assert\NotBlank]
        #[Assert\Regex('/^(DE\d{9}|IT\d{11}|GR\d{9}|FR[A-Z]{2}\d{9})$/')]
        public string $taxNumber,

        public ?string $couponCode = null,
    )
    {
    }
}