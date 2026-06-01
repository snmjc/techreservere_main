<?php

namespace App\Shared\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;

trait ServiceResultResponseTrait
{
    use JsonResponseTrait;

    protected function serviceResultResponse(
        array $result,
        string $fallbackErrorCode = 'RequestFailed',
        string $fallbackMessage = 'Unable to complete the request.'
    ): JsonResponse {
        if (($result['success'] ?? false) === true) {
            return $this->createSuccessResponse($result['data'] ?? [], (int)($result['status'] ?? 200));
        }

        return $this->createErrorResponse(
            (string)($result['errorCode'] ?? $fallbackErrorCode),
            (string)($result['message'] ?? $fallbackMessage),
            (int)($result['status'] ?? 500),
            $result['extra'] ?? []
        );
    }
}
