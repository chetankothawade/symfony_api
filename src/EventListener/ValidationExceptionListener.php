<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ValidationExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Handle UnprocessableEntityHttpException (from MapRequestPayload validation failures)
        if ($exception instanceof UnprocessableEntityHttpException) {
            $errors = [];
            
            // Extract validation errors from the previous exception
            if ($exception->getPrevious()) {
                $previous = $exception->getPrevious();
                if (method_exists($previous, 'getViolations')) {
                    foreach ($previous->getViolations() as $violation) {
                        $field = $violation->getPropertyPath();
                        $errors[$field][] = $violation->getMessage();
                    }
                }
            }

            $response = new JsonResponse([
                'message' => 'Validation failed.',
                'errors' => !empty($errors) ? $errors : [],
            ], 422);

            $event->setResponse($response);
        }
    }
}
