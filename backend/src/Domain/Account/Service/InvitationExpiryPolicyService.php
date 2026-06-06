<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AppClock;

class InvitationExpiryPolicyService
{
    private const DEFAULT_PRODUCTION_EXPIRATION_DAYS = 7;
    private const DEFAULT_TEST_EXPIRATION_MINUTES = 5;

    public function buildExpiresAt(\DateTimeImmutable $createdAt): \DateTimeImmutable
    {
        if ($this->usesTestingExpiration()) {
            return $createdAt->modify(sprintf('+%d minutes', $this->testingExpirationMinutes()));
        }

        return $createdAt->modify(sprintf('+%d days', $this->productionExpirationDays()));
    }

    public function resolveStoredExpiresAt(\DateTimeImmutable $localExpiresAt, ?\DateTimeImmutable $clerkExpiresAt): \DateTimeImmutable
    {
        if ($this->usesTestingExpiration()) {
            return $localExpiresAt;
        }

        return $clerkExpiresAt ?? $localExpiresAt;
    }

    public function clerkExpiresInDays(): int
    {
        if ($this->usesTestingExpiration()) {
            return 1;
        }

        return $this->productionExpirationDays();
    }

    public function usesTestingExpiration(): bool
    {
        $environment = strtolower(trim((string)($_ENV['APP_ENV'] ?? 'prod')));
        return in_array($environment, ['test', 'testing'], true);
    }

    public function currentPolicySummary(): array
    {
        if ($this->usesTestingExpiration()) {
            return [
                'mode' => 'testing',
                'minutes' => $this->testingExpirationMinutes(),
                'timezone' => AppClock::timezone()->getName(),
            ];
        }

        return [
            'mode' => 'production',
            'days' => $this->productionExpirationDays(),
            'timezone' => AppClock::timezone()->getName(),
        ];
    }

    private function productionExpirationDays(): int
    {
        $configuredDays = (int)($_ENV['INVITATION_EXPIRATION_DAYS'] ?? self::DEFAULT_PRODUCTION_EXPIRATION_DAYS);
        return $configuredDays > 0 ? $configuredDays : self::DEFAULT_PRODUCTION_EXPIRATION_DAYS;
    }

    private function testingExpirationMinutes(): int
    {
        $configuredMinutes = (int)($_ENV['INVITATION_TEST_EXPIRATION_MINUTES'] ?? self::DEFAULT_TEST_EXPIRATION_MINUTES);
        return $configuredMinutes > 0 ? $configuredMinutes : self::DEFAULT_TEST_EXPIRATION_MINUTES;
    }
}
