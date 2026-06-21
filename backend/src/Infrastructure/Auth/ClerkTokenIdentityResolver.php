<?php

namespace App\Infrastructure\Auth;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Service\ClerkInvitationSyncService;

class ClerkTokenIdentityResolver
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly JwtPayloadDecoder $jwtPayloadDecoder,
        private readonly ClerkPrimaryEmailResolver $clerkPrimaryEmailResolver,
        private readonly AccountIdentityBuilder $accountIdentityBuilder,
        private readonly ClerkInvitationSyncService $clerkInvitationSyncService
    ) {
    }

    public function resolve(string $bearerToken): array
    {
        $payload = $this->jwtPayloadDecoder->decode($bearerToken);
        $clerkUserId = (string)($payload['sub'] ?? '');

        if ($clerkUserId === '') {
            throw new ClerkVerificationFailedException('JWT missing sub claim (Clerk user ID).');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new ClerkVerificationFailedException('Clerk session token has expired.');
        }

        $emailAddress = $this->resolvePrimaryEmailAddress($clerkUserId);
        $account = $this->findResolvedAccount($clerkUserId, $emailAddress);

        if ($account !== null && $emailAddress !== '') {
            $account = $this->synchronizeAcceptedInvitationAccount($account, $emailAddress, $clerkUserId);
        }

        if ($account === null) {
            throw new ClerkVerificationFailedException('Account not found for clerkUserId: ' . $clerkUserId);
        }

        try {
            $this->accountIdentityBuilder->validateApprovedAccount($account);
        } catch (ClerkVerificationFailedException $exception) {
            if ($emailAddress === '') {
                throw $exception;
            }

            $account = $this->synchronizeAcceptedInvitationAccount($account, $emailAddress, $clerkUserId);
            $this->accountIdentityBuilder->validateApprovedAccount($account);
        }

        return $this->accountIdentityBuilder->build($account, $clerkUserId);
    }

    private function resolvePrimaryEmailAddress(string $clerkUserId): string
    {
        return $this->clerkPrimaryEmailResolver->resolve($clerkUserId);
    }

    private function findResolvedAccount(string $clerkUserId, string $emailAddress): ?\App\Domain\Account\Entity\AccountEntity
    {
        $account = $this->accountRepository->findOneByClerkUserId($clerkUserId);
        if ($account !== null) {
            return $account;
        }

        if ($emailAddress === '') {
            return null;
        }

        $account = $this->accountRepository->findOneByEmailAddress($emailAddress);
        if ($account !== null && $this->canAttachClerkUserId($account, $clerkUserId)) {
            $account->setClerkUserId($clerkUserId);
            $this->accountRepository->persistAccount($account);
        }

        return $account;
    }

    private function synchronizeAcceptedInvitationAccount(
        \App\Domain\Account\Entity\AccountEntity $account,
        string $emailAddress,
        string $clerkUserId
    ): \App\Domain\Account\Entity\AccountEntity {
        $this->clerkInvitationSyncService->syncAcceptedInvitationForEmail($emailAddress, $clerkUserId);

        return $this->accountRepository->findOneByClerkUserId($clerkUserId)
            ?? $this->accountRepository->findOneByEmailAddress($emailAddress)
            ?? $account;
    }

    private function canAttachClerkUserId(\App\Domain\Account\Entity\AccountEntity $account, string $clerkUserId): bool
    {
        return $account->getClerkUserId() !== $clerkUserId
            && $account->getIsVerified()
            && $account->getIsActive()
            && in_array(strtolower($account->getStatus()), ['active', 'approved', 'accepted'], true);
    }
}
