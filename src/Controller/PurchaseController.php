<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\PurchaseRequest;
use App\Service\CalculatePriceService;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

class PurchaseController extends AbstractController
{
    public function __construct(
        private readonly CalculatePriceService $calculatePriceService,
        private readonly PaymentService $paymentService,
    )
    {
    }

    #[Route('/purchase', name: 'purchase', methods: ['POST'])]
    public function purchase(#[MapRequestPayload] PurchaseRequest $request): JsonResponse
    {
        $price = $this->calculatePriceService->calculatePrice($request);
        $this->paymentService->processPayment($request->paymentProcessor, $price);
        return new JsonResponse(['result' => 'ok'], JsonResponse::HTTP_OK);
    }
}