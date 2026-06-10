<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Service\ClerkWebhookSignatureValidator;
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
        private readonly ClerkWebhookSignatureValidator $signatureValidator,
        private readonly ClerkWebhookUserSyncService $userSyncService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/api/v1/clerk/webhook', name: 'clerk_webhook', methods: ['POST'])]
    #[Route('/api/clerk/webhook', name: 'clerk_webhook_legacy', methods: ['POST'])]
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        if (!$this->signatureValidator->isValid($request, $payload)) {
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
}
