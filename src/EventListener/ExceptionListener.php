<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ValidationFailedException) {
            $errors = [];
            foreach ($exception->getViolations() as $violation) {
                $errors[] = [
                    'propertyPath' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            $response = new JsonResponse(['error' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            $event->setResponse($response);
        } elseif ($exception instanceof \InvalidArgumentException || $exception instanceof \UnexpectedValueException) {
            $response = new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            $event->setResponse($response);
        } elseif ($exception instanceof HttpExceptionInterface) {
            $response = new JsonResponse(['error' => $exception->getMessage()], $exception->getStatusCode());
            $event->setResponse($response);
        }
    }
}
