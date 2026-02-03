<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    protected function success(string $message, array $data = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error(string $message, array $errors = [], int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
