<?php

namespace App\Domain\Account\Service;

use App\Domain\Account\Repository\AccountRepository;
use App\Shared\Utils\AccountUsername;
use Psr\Log\LoggerInterface;

class ClerkWebhookService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(array $event): array
    {
        $eventType = (string)($event['type'] ?? '');
        $data = is_array($event['data'] ?? null) ? $event['data'] : [];

        return match ($eventType) {
            'user.created' => $this->handleUserCreated($data),
            'user.updated' => $this->handleUserUpdated($data),
            'user.deleted' => $this->handleUserDeleted($data),
            default => [
                'handled' => false,
                'eventType' => $eventType,
                'message' => 'Webhook event ignored.',
            ],
        };
    }

    private function handleUserCreated(array $data): array
    {
        $attributes = $this->buildUserAttributes($data);
        if ($attributes === null) {
            throw new \InvalidArgumentException('Clerk user.created payload is missing a user id or primary email address.');
        }

        $result = $this->accountRepository->upsertClerkAccount($attributes);

        return [
            'handled' => true,
            'eventType' => 'user.created',
            'message' => ($result['created'] ?? false) === true
                ? 'Clerk user created and inserted into PostgreSQL.'
                : 'Existing account synchronized and automatically approved from Clerk invitation acceptance.',
            'account' => $result,
        ];
    }

    private function handleUserUpdated(array $data): array
    {
        $attributes = $this->buildUserAttributes($data);
        if ($attributes === null) {
            throw new \InvalidArgumentException('Clerk user.updated payload is missing a user id or primary email address.');
        }

        $result = $this->accountRepository->updateClerkAccountFromWebhook($attributes)
            ?? $this->accountRepository->upsertClerkAccount($attributes);

        return [
            'handled' => true,
            'eventType' => 'user.updated',
            'message' => 'Clerk user changes synchronized into PostgreSQL.',
            'account' => $result,
        ];
    }

    private function handleUserDeleted(array $data): array
    {
        $clerkUserId = trim((string)($data['id'] ?? ''));
        $emailAddress = $this->resolvePrimaryEmailAddress($data);

        if ($clerkUserId === '' && $emailAddress === '') {
            throw new \InvalidArgumentException('Clerk user.deleted payload is missing both user id and email address.');
        }

        $result = $this->accountRepository->softDeleteClerkAccount($clerkUserId, $emailAddress);
        if ($result === null) {
            $this->logger->warning('Clerk user.deleted webhook could not find a matching local account to soft delete.', [
                'clerkUserId' => $clerkUserId,
                'emailAddress' => $emailAddress,
            ]);

            return [
                'handled' => true,
                'eventType' => 'user.deleted',
                'message' => 'No local account matched the deleted Clerk user.',
                'account' => null,
            ];
        }

        return [
            'handled' => true,
            'eventType' => 'user.deleted',
            'message' => 'Clerk user soft deleted in PostgreSQL.',
            'account' => $result,
        ];
    }

    private function buildUserAttributes(array $data): ?array
    {
        $clerkUserId = trim((string)($data['id'] ?? ''));
        $emailAddress = $this->resolvePrimaryEmailAddress($data);

        if ($clerkUserId === '' || $emailAddress === '') {
            return null;
        }

        return [
            'clerkUserId' => $clerkUserId,
            'emailAddress' => $emailAddress,
            'firstName' => trim((string)($data['first_name'] ?? $data['firstName'] ?? '')),
            'lastName' => trim((string)($data['last_name'] ?? $data['lastName'] ?? '')),
            'username' => AccountUsername::fromEmail($emailAddress),
            'roleDesignation' => $this->resolveRoleDesignation($data),
            'isActive' => true,
            'isApproved' => true,
            'status' => 'approved',
        ];
    }

    private function resolvePrimaryEmailAddress(array $userData): string
    {
        $primaryEmailAddressId = (string)($userData['primary_email_address_id'] ?? '');
        $emailAddresses = is_array($userData['email_addresses'] ?? null) ? $userData['email_addresses'] : [];

        foreach ($emailAddresses as $emailAddress) {
            if ((string)($emailAddress['id'] ?? '') === $primaryEmailAddressId) {
                return strtolower(trim((string)($emailAddress['email_address'] ?? '')));
            }
        }

        $fallbackEmail = (string)($emailAddresses[0]['email_address'] ?? '');
        if ($fallbackEmail !== '') {
            return strtolower(trim($fallbackEmail));
        }

        return strtolower(trim((string)(
            $userData['email_address']
            ?? $userData['emailAddress']
            ?? $userData['unsafe_metadata']['emailAddress']
            ?? $userData['public_metadata']['emailAddress']
            ?? ''
        )));
    }

    private function resolveRoleDesignation(array $userData): string
    {
        $candidate = strtoupper(trim((string)(
            $userData['public_metadata']['roleDesignation']
            ?? $userData['public_metadata']['role']
            ?? $userData['unsafe_metadata']['roleDesignation']
            ?? $userData['unsafe_metadata']['role']
            ?? ''
        )));

        return match (true) {
            $candidate === 'ROLE_ADMIN', $candidate === 'ADMIN' => 'ROLE_ADMIN',
            $candidate === 'ROLE_STAFF', $candidate === 'STAFF', $candidate === 'EMPLOYEE', $candidate === 'ROLE_EMPLOYEE' => 'ROLE_STAFF',
            $candidate === 'ROLE_BORROWER', $candidate === 'BORROWER', $candidate === 'ROLE_FACULTY', $candidate === 'FACULTY' => 'ROLE_BORROWER',
            str_starts_with($candidate, 'ROLE_') => $candidate,
            default => 'ROLE_BORROWER',
        };
    }

}
