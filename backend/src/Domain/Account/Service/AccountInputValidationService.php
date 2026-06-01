<?php

namespace App\Domain\Account\Service;

class AccountInputValidationService
{
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
        $normalizedEmailAddress = strtolower(trim($emailAddress));

        return str_ends_with($normalizedEmailAddress, '@fit.edu.ph')
            || str_ends_with($normalizedEmailAddress, '@techreserve.edu.ph');
    }
}
