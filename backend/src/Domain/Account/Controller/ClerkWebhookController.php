<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Service\ClerkWebhookService;
use App\Domain\Account\Service\ClerkWebhookSignatureValidator;
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
        private readonly ClerkWebhookService $clerkWebhookService,
        private readonly LoggerInterface $logger
    )
    {
    }

    #[Route('/api/v1/clerk/webhook', name: 'clerk_webhook', methods: ['POST'])]
    #[Route('/api/clerk/webhook', name: 'clerk_webhook_legacy', methods: ['POST'])]
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        if (!$this->signatureValidator->isValid(
            $payload,
            (string)$request->headers->get('svix-id', ''),
            (string)$request->headers->get('svix-timestamp', ''),
            (string)$request->headers->get('svix-signature', '')
        )) {
            return $this->createErrorResponse('InvalidSignature', 'Invalid Clerk webhook signature.', 401);
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            return $this->createErrorResponse('InvalidPayload', 'Invalid Clerk webhook payload.', 400);
        }

        try {
            $result = $this->clerkWebhookService->handle($event);
        } catch (\Throwable $exception) {
            $eventType = (string)($event['type'] ?? '');
            $this->logger->error('Clerk webhook processing failed.', [
                'eventType' => $eventType,
                'error' => $exception->getMessage(),
            ]);

            return $this->createErrorResponse(
                'WebhookProcessingFailed',
                'Clerk webhook processing failed.',
                500
            );
        }

        return $this->createSuccessResponse([
            'received' => true,
            'eventType' => $result['eventType'] ?? (string)($event['type'] ?? ''),
            'handled' => (bool)($result['handled'] ?? false),
            'message' => (string)($result['message'] ?? 'Webhook processed.'),
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
