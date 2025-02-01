<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\PaymentProcessor;
use Symfony\Component\Validator\Constraints as Assert;

class PurchaseRequest extends BasePriceRequest
{
    public function __construct(
        int $product,
        string $taxNumber,
        ?string $couponCode,

        #[Assert\NotBlank]
        public PaymentProcessor $paymentProcessor,
    )
    {
        parent::__construct($product, $taxNumber, $couponCode);
    }
}