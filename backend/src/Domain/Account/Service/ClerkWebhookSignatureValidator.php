<?php

namespace App\Domain\Account\Service;

use Symfony\Component\HttpFoundation\Request;

class ClerkWebhookSignatureValidator
{
    public function isValid(Request $request, string $payload): bool
    {
        $signingSecret = trim((string)($_ENV['CLERK_WEBHOOK_SIGNING_SECRET'] ?? ''));
        if ($signingSecret === '') {
            return false;
        }

        $svixId = (string)$request->headers->get('svix-id', '');
        $svixTimestamp = (string)$request->headers->get('svix-timestamp', '');
        $svixSignature = (string)$request->headers->get('svix-signature', '');

        if ($svixId === '' || $svixTimestamp === '' || $svixSignature === '') {
            return false;
        }

        $secret = str_starts_with($signingSecret, 'whsec_')
            ? substr($signingSecret, 6)
            : $signingSecret;
        $decodedSecret = base64_decode($secret, true);
        if ($decodedSecret === false) {
            return false;
        }

        $signedPayload = $svixId . '.' . $svixTimestamp . '.' . $payload;
        $expectedSignature = base64_encode(hash_hmac('sha256', $signedPayload, $decodedSecret, true));

        foreach (explode(' ', $svixSignature) as $signaturePart) {
            $signature = str_starts_with($signaturePart, 'v1,')
                ? substr($signaturePart, 3)
                : $signaturePart;

            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }
}
