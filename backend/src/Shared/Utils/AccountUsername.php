<?php

namespace App\Shared\Utils;

class AccountUsername
{
    public static function fromEmail(string $emailAddress): string
    {
        $localPart = strtolower(trim(strtok($emailAddress, '@') ?: $emailAddress));
        $username = preg_replace('/[^a-z0-9._-]+/', '', $localPart) ?: '';
        return trim($username, '._-') ?: 'user';
    }
}
