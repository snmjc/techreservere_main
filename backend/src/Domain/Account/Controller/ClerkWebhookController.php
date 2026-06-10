<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Service\ClerkWebhookUserSyncService;
use App\Shared\Traits\JsonResponseTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ClerkWebhookController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly ClerkWebhookUserSyncService $userSyncService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/api/v1/clerk/webhook', name: 'clerk_webhook', methods: ['POST'])]
    #[Route('/api/clerk/webhook', name: 'clerk_webhook_legacy', methods: ['POST'])]
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        if (!$this->isValidClerkSignature($request, $payload)) {
            return $this->createErrorResponse('InvalidSignature', 'Invalid Clerk webhook signature.', 401);
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            return $this->createErrorResponse('InvalidPayload', 'Invalid Clerk webhook payload.', 400);
        }

        $eventType = (string)($event['type'] ?? '');
        $data = is_array($event['data'] ?? null) ? $event['data'] : [];

        $this->logger->info('Clerk webhook received.', [
            'eventType' => $eventType,
            'clerkUserId' => $data['id'] ?? null,
        ]);

        if (in_array($eventType, ['user.created', 'user.updated'], true)) {
            $this->userSyncService->sync($data);
        }

        return $this->createSuccessResponse([
            'received' => true,
            'eventType' => $eventType,
        ]);
    }

    #[Route('/api/v1/clerk/webhook', name: 'clerk_webhook_status', methods: ['GET'])]
    #[Route('/api/clerk/webhook', name: 'clerk_webhook_legacy_status', methods: ['GET'])]
    public function webhookStatus(): JsonResponse
    {
        return $this->createSuccessResponse([
            'message' => 'Clerk webhook endpoint is available. Configure Clerk to send POST requests to this URL.',
            'canonicalPath' => '/api/v1/clerk/webhook',
            'legacyPath' => '/api/clerk/webhook',
        ]);
    }

    private function isValidClerkSignature(Request $request, string $payload): bool
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
