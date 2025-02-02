<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\PaymentProcessor;
use App\Exception\PaymentException;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

class PaymentService
{
    private readonly StripePaymentProcessor $stripePaymentProcessor;
    private readonly PaypalPaymentProcessor $paypalPaymentProcessor;

    public function __construct()
    {
        $this->stripePaymentProcessor = new StripePaymentProcessor();
        $this->paypalPaymentProcessor = new PaypalPaymentProcessor();
    }

    /**
     * @param PaymentProcessor $paymentProcessor
     * @param int $price Price in cents
     * @return void
     */
    public function processPayment(PaymentProcessor $paymentProcessor, int $price): void
    {
        $result = match ($paymentProcessor) {
            PaymentProcessor::Stripe => $this->processStripe($price),
            PaymentProcessor::PayPal => $this->processPayPal($price),
        };
        if (!$result) {
            throw new PaymentException('Payment failed');
        }
    }

    private function processStripe(int $price): bool
    {
        return $this->stripePaymentProcessor->processPayment($price / 100);
    }

    private function processPayPal(int $price): bool
    {
        try {
            $this->paypalPaymentProcessor->pay($price);
            return true;
        } catch (\Exception $e) {
            // TODO: Log error
            return false;
        }
    }
}