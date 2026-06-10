<?php

namespace App\Domain\Account\Service;

class AccountInputValidationService
{
<<<<<<< HEAD
    private const USER_EMAIL_DOMAINS = [
        '@fit.edu.ph',
        '@feutech.edu.ph',
    ];

    private const ADMIN_EMAIL_DOMAINS = [
        '@feutech.edu.ph',
        '@fit.edu.ph',
=======
    private const DEFAULT_ALLOWED_ADMIN_EMAIL_DOMAINS = [
        'feutech.edu.ph',
    ];

    private const DEFAULT_ALLOWED_REQUEST_HUB_USER_EMAIL_DOMAINS = [
        'fit.edu.ph',
        'feutech.edu.ph',
>>>>>>> bc5ccad99854238cde43860e32aba3ff56c4d845
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
<<<<<<< HEAD
        return $this->hasAllowedDomain($emailAddress, self::ADMIN_EMAIL_DOMAINS);
    }

    public function isInstitutionalUserEmail(string $emailAddress): bool
    {
        return $this->hasAllowedDomain($emailAddress, self::USER_EMAIL_DOMAINS);
    }

    private function hasAllowedDomain(string $emailAddress, array $allowedDomains): bool
=======
        $normalizedEmailAddress = strtolower(trim($emailAddress));
        if (!filter_var($normalizedEmailAddress, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = substr(strrchr($normalizedEmailAddress, '@') ?: '', 1);
        if ($domain === '') {
            return false;
        }

        if (in_array($domain, $this->blockedAdminEmailDomains(), true)) {
            return false;
        }

        return in_array($domain, $this->allowedAdminEmailDomains(), true);
    }

    public function normalizeIdNumber(string $idNumber): string
    {
        return trim(preg_replace('/\s+/', '', $idNumber) ?? $idNumber);
    }

    public function isAllowedRequestHubUserEmail(string $emailAddress): bool
>>>>>>> bc5ccad99854238cde43860e32aba3ff56c4d845
    {
        $normalizedEmailAddress = strtolower(trim($emailAddress));
        if (!filter_var($normalizedEmailAddress, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

<<<<<<< HEAD
        foreach ($allowedDomains as $allowedDomain) {
            if (str_ends_with($normalizedEmailAddress, $allowedDomain)) {
                return true;
            }
        }

        return false;
=======
        $domain = substr(strrchr($normalizedEmailAddress, '@') ?: '', 1);
        if ($domain === '') {
            return false;
        }

        return in_array($domain, self::DEFAULT_ALLOWED_REQUEST_HUB_USER_EMAIL_DOMAINS, true);
    }

    public function isValidIdNumber(string $idNumber): bool
    {
        return $this->normalizeIdNumber($idNumber) !== '';
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

    public function blockedAdminEmailDomains(): array
    {
        return ['techreserve.edu.ph', 'techreserve.feu.edu.ph'];
>>>>>>> bc5ccad99854238cde43860e32aba3ff56c4d845
    }
}
