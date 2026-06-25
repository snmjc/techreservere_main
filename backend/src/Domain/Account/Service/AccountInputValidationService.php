<?php

namespace App\Domain\Account\Service;

class AccountInputValidationService
{
    private const USER_EMAIL_DOMAINS = [
        '@fit.edu.ph',
        '@feutech.edu.ph',
        '@techreserve.edu.ph',
    ];

    private const ADMIN_EMAIL_DOMAINS = [
        '@feutech.edu.ph',
        '@techreserve.edu.ph',
    ];

    public function isValidPersonName(string $name): bool
    {
        $normalizedName = $this->normalizePersonName($name);
        preg_match_all('/[A-Za-z]/', $normalizedName, $letterMatches);

        return count($letterMatches[0] ?? []) >= 2
            && preg_match('/^[A-Za-z ]+$/', $normalizedName) === 1;
    }

    public function normalizePersonName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    public function isInstitutionalAdminEmail(string $emailAddress): bool
    {
        return $this->hasAllowedDomain($emailAddress, self::ADMIN_EMAIL_DOMAINS);
    }

    public function isInstitutionalUserEmail(string $emailAddress): bool
    {
        return $this->hasAllowedDomain($emailAddress, self::USER_EMAIL_DOMAINS);
    }

    public function normalizeIdNumber(string $idNumber): string
    {
        return trim(preg_replace('/\s+/', '', $idNumber) ?? $idNumber);
    }

    public function isAllowedRequestHubUserEmail(string $emailAddress): bool
    {
        return $this->isInstitutionalUserEmail($emailAddress);
    }

    public function isValidIdNumber(string $idNumber): bool
    {
        return preg_match('/^\d{9}$/', $this->normalizeIdNumber($idNumber)) === 1;
    }

    public function isValidInstitutionalSignInEmail(string $emailAddress): bool
    {
        return $this->isInstitutionalUserEmail($emailAddress) || $this->isInstitutionalAdminEmail($emailAddress);
    }

    private function hasAllowedDomain(string $emailAddress, array $allowedDomains): bool
    {
        $normalizedEmailAddress = strtolower(trim($emailAddress));
        if (!filter_var($normalizedEmailAddress, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        foreach ($allowedDomains as $allowedDomain) {
            if (str_ends_with($normalizedEmailAddress, $allowedDomain)) {
                return true;
            }
        }

        return false;
    }
}
