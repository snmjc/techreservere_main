<?php

namespace App\Domain\Authentication\Service;

class PasswordPolicyService
{
    public function isStrongPassword(string $password): bool
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password) === 1;
    }
}
