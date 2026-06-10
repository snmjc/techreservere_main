<?php

namespace App\Domain\Account\Service;

use Psr\Log\LoggerInterface;

class ClerkWebhookSignatureValidator
{
    private const DEFAULT_TOLERANCE_SECONDS = 300;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function isValid(string $payload, string $svixId, string $svixTimestamp, string $svixSignature): bool
    {
        $signingSecret = trim((string)($_ENV['CLERK_WEBHOOK_SIGNING_SECRET'] ?? ''));
        if ($signingSecret === '') {
            $this->logger->error('Clerk webhook rejected because CLERK_WEBHOOK_SIGNING_SECRET is not configured.');
            return false;
        }

        if ($svixId === '' || $svixTimestamp === '' || $svixSignature === '') {
            $this->logger->warning('Clerk webhook rejected because one or more Svix headers are missing.', [
                'svixIdPresent' => $svixId !== '',
                'svixTimestampPresent' => $svixTimestamp !== '',
                'svixSignaturePresent' => $svixSignature !== '',
            ]);
            return false;
        }

        if (!$this->isFreshTimestamp($svixTimestamp)) {
            $this->logger->warning('Clerk webhook rejected because the Svix timestamp is outside the allowed tolerance.', [
                'svixTimestamp' => $svixTimestamp,
            ]);
            return false;
        }

        $secret = str_starts_with($signingSecret, 'whsec_')
            ? substr($signingSecret, 6)
            : $signingSecret;
        $decodedSecret = base64_decode($secret, true);
        if ($decodedSecret === false) {
            $this->logger->error('Clerk webhook rejected because the configured signing secret could not be base64 decoded.');
            return false;
        }

        $signedPayload = $svixId . '.' . $svixTimestamp . '.' . $payload;
        $expectedSignature = base64_encode(hash_hmac('sha256', $signedPayload, $decodedSecret, true));

        foreach (preg_split('/\s+/', trim($svixSignature)) ?: [] as $signaturePart) {
            $signature = str_starts_with($signaturePart, 'v1,')
                ? substr($signaturePart, 3)
                : $signaturePart;

            if ($signature !== '' && hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        $this->logger->warning('Clerk webhook rejected because signature verification failed.', [
            'svixId' => $svixId,
        ]);
        return false;
    }

    private function isFreshTimestamp(string $svixTimestamp): bool
    {
        if (!ctype_digit($svixTimestamp)) {
            return false;
        }

        return abs(time() - (int)$svixTimestamp) <= self::DEFAULT_TOLERANCE_SECONDS;
    }
}
