<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DTO\PurchaseRequest;
use App\Enum\PaymentProcessor;
use App\Service\CalculatePriceService;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PurchaseControllerTest extends WebTestCase
{
    private $client;
    private $calculatePriceServiceMock;
    private $paymentServiceMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->calculatePriceServiceMock = $this->createMock(CalculatePriceService::class);
        $this->paymentServiceMock = $this->createMock(PaymentService::class);
        static::getContainer()->set(CalculatePriceService::class, $this->calculatePriceServiceMock);
        static::getContainer()->set(PaymentService::class, $this->paymentServiceMock);
    }

    public function testPurchaseSuccess(): void
    {
        $this->calculatePriceServiceMock->expects($this->once())
            ->method('calculatePrice')
            ->willReturn(10000);

        $this->paymentServiceMock->expects($this->once())
            ->method('processPayment')
            ->with(PaymentProcessor::PayPal, 10000);

        $this->client->request('POST', '/purchase', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => 1,
            'taxNumber' => 'DE123456789',
            'couponCode' => 'DISCOUNT10',
            'paymentProcessor' => 'paypal'
        ]));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['result' => 'ok']),
            $this->client->getResponse()->getContent()
        );
    }

    public function testPurchaseInvalidCoupon(): void
    {
        $this->calculatePriceServiceMock->expects($this->once())
            ->method('calculatePrice')
            ->willThrowException(new \InvalidArgumentException('Invalid coupon code'));

        $this->client->request('POST', '/purchase', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => 1,
            'taxNumber' => 'DE123456789',
            'couponCode' => 'INVALID',
            'paymentProcessor' => 'paypal'
        ]));

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Invalid coupon code']),
            $this->client->getResponse()->getContent()
        );
    }

    public function testPurchaseInvalidPaymentProcessor(): void
    {
        $this->client->request('POST', '/purchase', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => 1,
            'taxNumber' => 'DE123456789',
            'couponCode' => 'DISCOUNT10',
            'paymentProcessor' => 'invalid_processor'
        ]));

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'The data must belong to a backed enumeration of type App\\Enum\\PaymentProcessor']),
            $this->client->getResponse()->getContent()
        );
    }

    public function testPurchaseProductNotFound(): void
    {
        $this->calculatePriceServiceMock->expects($this->once())
            ->method('calculatePrice')
            ->willThrowException(new \InvalidArgumentException('Product not found'));

        $this->client->request('POST', '/purchase', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => 999,
            'taxNumber' => 'DE123456789',
            'couponCode' => 'DISCOUNT10',
            'paymentProcessor' => 'paypal'
        ]));

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Product not found']),
            $this->client->getResponse()->getContent()
        );
    }

    public function testPurchaseValidationFailed(): void
    {
        $this->client->request('POST', '/purchase', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => 0,
            'taxNumber' => 'INVALID',
            'couponCode' => 'DISCOUNT10',
            'paymentProcessor' => 'paypal'
        ]));

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
    }
}