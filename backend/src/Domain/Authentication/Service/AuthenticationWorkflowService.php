<?php

namespace App\Domain\Authentication\Service;

class AuthenticationWorkflowService
{
    public function __construct(
        private readonly AuthenticationLoginService $authenticationLoginService,
        private readonly ClerkLoginPreflightService $clerkLoginPreflightService,
        private readonly PasswordResetCodeService $passwordResetCodeService,
        private readonly AuthenticationRegistrationService $authenticationRegistrationService
    ) {
    }

    public function login(string $emailAddress, string $passwordText): array
    {
        return $this->authenticationLoginService->login($emailAddress, $passwordText);
    }

    public function clerkLoginPreflight(string $emailAddress): array
    {
        return $this->clerkLoginPreflightService->check($emailAddress);
    }

    public function requestPasswordReset(string $emailAddress): array
    {
        return $this->passwordResetCodeService->requestReset($emailAddress);
    }

    public function confirmPasswordReset(string $emailAddress, string $code, string $newPassword, string $confirmPassword): array
    {
        return $this->passwordResetCodeService->confirmReset($emailAddress, $code, $newPassword, $confirmPassword);
    }

    public function register(string $firstName, string $lastName, string $emailAddress, string $passwordText): array
    {
        return $this->authenticationRegistrationService->register($firstName, $lastName, $emailAddress, $passwordText);
    }
}
