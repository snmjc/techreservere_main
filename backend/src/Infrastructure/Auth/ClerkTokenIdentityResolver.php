<?php

namespace App\Infrastructure\Auth;

use App\Domain\Account\Repository\AccountRepository;

class ClerkTokenIdentityResolver
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly JwtPayloadDecoder $jwtPayloadDecoder,
        private readonly ClerkPrimaryEmailResolver $clerkPrimaryEmailResolver,
        private readonly AccountIdentityBuilder $accountIdentityBuilder
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

        $account = $this->accountRepository->findOneByClerkUserId($clerkUserId) ?? $this->findAccountByPrimaryEmail($clerkUserId);
        if ($account === null) {
            throw new ClerkVerificationFailedException('Account not found for clerkUserId: ' . $clerkUserId);
        }

        $this->accountIdentityBuilder->validateApprovedAccount($account);
        return $this->accountIdentityBuilder->build($account, $clerkUserId);
    }

    private function findAccountByPrimaryEmail(string $clerkUserId): ?\App\Domain\Account\Entity\AccountEntity
    {
        $emailAddress = $this->clerkPrimaryEmailResolver->resolve($clerkUserId);
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

    private function canAttachClerkUserId(\App\Domain\Account\Entity\AccountEntity $account, string $clerkUserId): bool
    {
        return $account->getClerkUserId() !== $clerkUserId
            && $account->getIsApproved()
            && $account->getIsActive()
            && strtolower($account->getStatus()) === 'approved';
    }
}
