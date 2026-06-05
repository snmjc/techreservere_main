<?php

namespace App\Domain\Account\Service;

class AccountInputValidationService
{
    private const DEFAULT_ALLOWED_ADMIN_EMAIL_DOMAINS = [
        'techreserve.edu.ph',
        'techreserve.feu.edu.ph',
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
        $normalizedEmailAddress = strtolower(trim($emailAddress));
        if (!filter_var($normalizedEmailAddress, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = substr(strrchr($normalizedEmailAddress, '@') ?: '', 1);
        if ($domain === '') {
            return false;
        }

        return in_array($domain, $this->allowedAdminEmailDomains(), true);
    }

    public function allowedAdminEmailDomains(): array
    {
        $configuredDomains = trim((string)($_ENV['ADMIN_EMAIL_DOMAINS'] ?? ''));
        if ($configuredDomains === '') {
            return self::DEFAULT_ALLOWED_ADMIN_EMAIL_DOMAINS;
        }

        $domains = array_values(array_filter(array_map(
            static fn (string $domain): string => strtolower(trim($domain)),
            explode(',', $configuredDomains)
        )));

        return $domains !== [] ? $domains : self::DEFAULT_ALLOWED_ADMIN_EMAIL_DOMAINS;
    }
}
