<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DTO\CalculatePriceRequest;
use App\Service\CalculatePriceService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PriceControllerTest extends WebTestCase
{
    private $client;
    private $calculatePriceServiceMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->calculatePriceServiceMock = $this->createMock(CalculatePriceService::class);
        static::getContainer()->set(CalculatePriceService::class, $this->calculatePriceServiceMock);
    }

    public function testCalculatePriceSuccess(): void
    {
        $this->calculatePriceServiceMock->expects($this->once())
            ->method('calculatePrice')
            ->willReturn(10000);

        $this->client->request('POST', '/calculate-price', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => 1,
            'taxNumber' => 'DE123456789',
        ]));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['price' => 100]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testCalculatePriceInvalidCoupon(): void
    {
        $this->calculatePriceServiceMock->expects($this->once())
            ->method('calculatePrice')
            ->willThrowException(new \InvalidArgumentException('Invalid coupon code'));

        $this->client->request('POST', '/calculate-price', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => 1,
            'taxNumber' => 'DE123456789',
            'couponCode' => 'INVALID'
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Invalid coupon code']),
            $this->client->getResponse()->getContent()
        );
    }

    public function testCalculatePriceProductNotFound(): void
    {
        $this->calculatePriceServiceMock->expects($this->once())
            ->method('calculatePrice')
            ->willThrowException(new \InvalidArgumentException('Product not found'));

        $this->client->request('POST', '/calculate-price', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => 999,
            'taxNumber' => 'DE123456789',
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Product not found']),
            $this->client->getResponse()->getContent()
        );
    }

    public function testCalculatePriceValidationFailed(): void
    {
        $this->client->request('POST', '/calculate-price', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => 0,
            'taxNumber' => 'INVALID',
            'couponCode' => 'DISCOUNT10'
        ]));

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
    }
}