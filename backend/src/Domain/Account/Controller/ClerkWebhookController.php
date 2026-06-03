<?php

namespace App\Domain\Account\Controller;

use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\AccountUsername;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ClerkWebhookController
{
    use JsonResponseTrait;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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

        if (in_array($eventType, ['user.created', 'user.updated'], true)) {
            $this->syncClerkUserToAccount($data);
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

    private function syncClerkUserToAccount(array $userData): void
    {
        $clerkUserId = trim((string)($userData['id'] ?? ''));
        $emailAddress = $this->resolvePrimaryEmailAddress($userData);

        if ($clerkUserId === '' || $emailAddress === '') {
            return;
        }

        $this->connection->executeStatement(
            "UPDATE accounts
             SET clerk_user_id = :clerkUserId,
                 username = COALESCE(NULLIF(username, ''), :username),
                 updated_timestamp = :updatedTimestamp
             WHERE LOWER(email_address) = LOWER(:emailAddress)
               AND (clerk_user_id IS NULL OR clerk_user_id = '' OR clerk_user_id = :clerkUserId)",
            [
                'clerkUserId' => $clerkUserId,
                'username' => AccountUsername::fromEmail($emailAddress),
                'updatedTimestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'emailAddress' => $emailAddress,
            ],
            [
                'clerkUserId' => ParameterType::STRING,
                'username' => ParameterType::STRING,
                'updatedTimestamp' => ParameterType::STRING,
                'emailAddress' => ParameterType::STRING,
            ]
        );
    }

    private function resolvePrimaryEmailAddress(array $userData): string
    {
        $primaryEmailAddressId = (string)($userData['primary_email_address_id'] ?? '');
        $emailAddresses = is_array($userData['email_addresses'] ?? null) ? $userData['email_addresses'] : [];

        foreach ($emailAddresses as $emailAddress) {
            if ((string)($emailAddress['id'] ?? '') === $primaryEmailAddressId) {
                return trim((string)($emailAddress['email_address'] ?? ''));
            }
        }

        return trim((string)($emailAddresses[0]['email_address'] ?? ''));
    }
}
