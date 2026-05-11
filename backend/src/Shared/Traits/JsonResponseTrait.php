<?php

namespace App\Shared\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;

trait JsonResponseTrait
{
    protected function createSuccessResponse($data, int $statusCode = 200): JsonResponse
    {
        $response = new JsonResponse([
            'success' => true,
            'data' => $data
        ], $statusCode);
        $this->addCorsHeaders($response);
        return $response;
    }

    protected function createErrorResponse(string $errorType, string $errorMessage, int $statusCode = 400): JsonResponse
    {
        $response = new JsonResponse([
            'success' => false,
            'errorType' => $errorType,
            'errorMessage' => $errorMessage
        ], $statusCode);
        $this->addCorsHeaders($response);
        return $response;
    }

    protected function addCorsHeaders(JsonResponse $response): void
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
