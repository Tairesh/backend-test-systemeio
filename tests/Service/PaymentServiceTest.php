<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Enum\PaymentProcessor;
use App\Exception\PaymentException;
use App\Service\PaymentService;
use PHPUnit\Framework\TestCase;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

class PaymentServiceTest extends TestCase
{
    private PaymentService $paymentService;
    private $paypalProcessorMock;
    private $stripeProcessorMock;

    protected function setUp(): void
    {
        $this->paypalProcessorMock = $this->createMock(PaypalPaymentProcessor::class);
        $this->stripeProcessorMock = $this->createMock(StripePaymentProcessor::class);

        $this->paymentService = new PaymentService($this->stripeProcessorMock, $this->paypalProcessorMock);
    }

    public function testProcessPaymentWithStripeSuccess(): void
    {
        $this->stripeProcessorMock->expects($this->once())
            ->method('processPayment')
            ->with(100.0)
            ->willReturn(true);

        $this->paymentService->processPayment(PaymentProcessor::Stripe, 10000);
    }

    public function testProcessPaymentWithPayPalSuccess(): void
    {
        $this->paypalProcessorMock->expects($this->once())
            ->method('pay')
            ->with(10000);

        $this->paymentService->processPayment(PaymentProcessor::PayPal, 10000);
    }

    public function testProcessPaymentWithInvalidProcessor(): void
    {
        $this->expectException(\TypeError::class);

        $this->paymentService->processPayment('invalid_processor', 10000);
    }

    public function testProcessPaymentWithStripeLowAmount(): void
    {
        $this->stripeProcessorMock->expects($this->once())
            ->method('processPayment')
            ->with(0.99)
            ->willReturn(false);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Payment failed');

        $this->paymentService->processPayment(PaymentProcessor::Stripe, 99);
    }

    public function testProcessPaymentWithPayPalHighAmount(): void
    {
        $this->paypalProcessorMock->expects($this->once())
            ->method('pay')
            ->with(100001)
            ->willThrowException(new \Exception('[#14271] Transaction "c82711ca-7e67-41c8-9f35-5b965e645d12" failed: Too high price'));

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Payment failed');

        $this->paymentService->processPayment(PaymentProcessor::PayPal, 100001);
    }
}