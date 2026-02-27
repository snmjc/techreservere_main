<?php

namespace App\Shared\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;

trait JsonResponseTrait
{
    // ===== AI GENERATED: createSuccessResponse =====
    // Purpose: Return standardized success JSON response
    // Inputs: responsePayload (array), statusCode (int)
    // Returns: JsonResponse
    // Flow:
    // 1. Wrap payload in standard shape
    // 2. Return JsonResponse with CORS header

    protected function createSuccessResponse(array $responsePayload, int $statusCode = 200): JsonResponse
    {
        return new JsonResponse($responsePayload, $statusCode, ['Access-Control-Allow-Origin' => '*']);
    }

    // ===== AI GENERATED: createErrorResponse =====
    // Purpose: Return standardized error JSON response
    // Inputs: errorCode (string), errorMessage (string), statusCode (int), errorDetails (array|null)
    // Returns: JsonResponse
    // Flow:
    // 1. Build error structure
    // 2. Return JsonResponse with CORS header

    protected function createErrorResponse(
        string $errorCode,
        string $errorMessage,
        int $statusCode = 400,
        ?array $errorDetails = null
    ): JsonResponse {
        $errorBody = [
            'errorCode' => $errorCode,
            'errorMessage' => $errorMessage,
        ];

        if ($errorDetails !== null) {
            $errorBody['errorDetails'] = $errorDetails;
        }

        return new JsonResponse($errorBody, $statusCode, ['Access-Control-Allow-Origin' => '*']);
    }
}
