<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\AccountUsername;

class ClerkInvitationPayloadFactory
{
    public function build(array $account, string $redirectUrl, bool $notify): array
    {
        $emailAddress = (string)($account['email_address'] ?? '');
        $accountIdentifier = (int)($account['account_identifier'] ?? 0);
        $roleDesignation = (string)($account['role_designation'] ?? '');
        $firstName = (string)($account['first_name'] ?? '');
        $lastName = (string)($account['last_name'] ?? '');

        return [
            'email_address' => $emailAddress,
            'redirect_url' => $redirectUrl,
            'notify' => $notify,
            'ignore_existing' => true,
            'expires_in_days' => 7,
            'public_metadata' => [
                'account_id' => $accountIdentifier,
                'email_address' => $emailAddress,
                'role_designation' => $roleDesignation,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'techreserve_account_identifier' => $accountIdentifier,
                'techreserve_username' => AccountUsername::fromEmail($emailAddress),
                'techreserve_role_designation' => $roleDesignation,
                'techreserve_first_name' => $firstName,
                'techreserve_last_name' => $lastName,
                'techreserve_email_address' => $emailAddress,
                'techreserve_id_number' => (string)($account['id_number'] ?? ''),
                'techreserve_department' => (string)($account['department'] ?? ''),
            ],
        ];
    }
}
