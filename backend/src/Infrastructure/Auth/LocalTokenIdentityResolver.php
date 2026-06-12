<?php

namespace App\Infrastructure\Auth;

use App\Domain\Account\Repository\AccountRepository;

class LocalTokenIdentityResolver
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly AccountIdentityBuilder $accountIdentityBuilder
    ) {
    }

    public function resolve(string $bearerToken): ?array
    {
        $decoded = json_decode(base64_decode($bearerToken), true);
        if (!is_array($decoded) || !isset($decoded['accountId'])) {
            return null;
        }

        $account = $this->accountRepository->find($decoded['accountId']);
        if ($account === null) {
            return $this->resolveDevAdminIdentity($decoded);
        }

        if (isset($decoded['exp']) && $decoded['exp'] < time()) {
            throw new ClerkVerificationFailedException('Token has expired');
        }

        $this->accountIdentityBuilder->validateApprovedAccount($account);
        return $this->accountIdentityBuilder->build($account);
    }

    private function resolveDevAdminIdentity(array $decoded): array
    {
        if (($_ENV['APP_ENV'] ?? 'prod') === 'dev' && ($decoded['role'] ?? null) === 'ROLE_ADMIN') {
            return [
                'accountIdentifier' => (int)$decoded['accountId'],
                'emailAddress' => (string)($decoded['email'] ?? 'local-admin@techreserve.dev'),
                'firstName' => 'Local',
                'lastName' => 'Admin',
                'roleDesignation' => 'ROLE_ADMIN',
                'status' => 'active',
                'isApproved' => true,
                'isVerified' => true,
            ];
        }

        throw new ClerkVerificationFailedException('Account not found for accountId: ' . $decoded['accountId']);
    }
}
