<?php

namespace App\Shared\Traits;

use Symfony\Component\HttpFoundation\Request;

trait RequestPayloadTrait
{
    protected function jsonBody(Request $request): array
    {
        $requestBody = json_decode($request->getContent(), true);

        return is_array($requestBody) ? $requestBody : [];
    }
}
