<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Repository\AccountRepository;
use App\Shared\Traits\JsonResponseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth')]
class AuthenticationController extends AbstractController
{
    use JsonResponseTrait;

    private AccountRepository $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $passwordText = $requestBody['passwordText'] ?? '';

        if (empty($emailAddress) || empty($passwordText)) {
            return $this->createErrorResponse(
                'ValidationError',
                'Email address and password are required.',
                400
            );
        }

        $accountEntity = $this->accountRepository->findOneByEmailAddress($emailAddress);

        if ($accountEntity === null) {
            return $this->createErrorResponse(
                'AuthenticationFailed',
                'Invalid email address or password.',
                401
            );
        }

        if (!$accountEntity->getIsActive()) {
            return $this->createErrorResponse(
                'AccountDisabled',
                'This account has been disabled. Please contact an administrator.',
                403
            );
        }

        $lockedUntil = $accountEntity->getLockedUntilTimestamp();
        if ($lockedUntil !== null && $lockedUntil > new \DateTime()) {
            return $this->createErrorResponse(
                'AccountLocked',
                'This account is temporarily locked due to too many failed login attempts.',
                403
            );
        }

        $storedHash = $accountEntity->getPasswordHash();

        if ($storedHash === null || !password_verify($passwordText, $storedHash)) {
            $failedAttempts = $accountEntity->getFailedLoginAttempts() + 1;
            $accountEntity->setFailedLoginAttempts($failedAttempts);

            if ($failedAttempts >= 5) {
                $accountEntity->setLockedUntilTimestamp(new \DateTime('+15 minutes'));
            }

            $this->accountRepository->persistAccount($accountEntity);

            return $this->createErrorResponse(
                'AuthenticationFailed',
                'Invalid email address or password.',
                401
            );
        }

        $accountEntity->setFailedLoginAttempts(0);
        $accountEntity->setLockedUntilTimestamp(null);
        $accountEntity->setLastLoginTimestamp(new \DateTime());
        $this->accountRepository->persistAccount($accountEntity);

        $tokenPayload = base64_encode(json_encode([
            'accountId' => $accountEntity->getAccountIdentifier(),
            'email' => $accountEntity->getEmailAddress(),
            'role' => $accountEntity->getRoleDesignation(),
            'exp' => time() + 86400,
        ]));

        return $this->createSuccessResponse([
            'token' => $tokenPayload,
            'account' => [
                'accountIdentifier' => $accountEntity->getAccountIdentifier(),
                'firstName' => $accountEntity->getFirstName(),
                'lastName' => $accountEntity->getLastName(),
                'emailAddress' => $accountEntity->getEmailAddress(),
                'roleDesignation' => $accountEntity->getRoleDesignation(),
            ],
        ]);
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $firstName = trim($requestBody['firstName'] ?? '');
        $lastName = trim($requestBody['lastName'] ?? '');
        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $passwordText = $requestBody['passwordText'] ?? '';

        if (empty($firstName) || empty($lastName) || empty($emailAddress) || empty($passwordText)) {
            return $this->createErrorResponse(
                'ValidationError',
                'First name, last name, email address, and password are required.',
                400
            );
        }

        if (strlen($passwordText) < 8) {
            return $this->createErrorResponse(
                'ValidationError',
                'Password must be at least 8 characters long.',
                400
            );
        }

        $existingAccount = $this->accountRepository->findOneByEmailAddress($emailAddress);

        if ($existingAccount !== null) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                'An account with this email address already exists.',
                409
            );
        }

        $accountEntity = new \App\Domain\Account\Entity\AccountEntity();
        $accountEntity->setFirstName($firstName);
        $accountEntity->setLastName($lastName);
        $accountEntity->setEmailAddress($emailAddress);
        $accountEntity->setPasswordHash(password_hash($passwordText, PASSWORD_BCRYPT));
        $accountEntity->setRoleDesignation('ROLE_BORROWER');

        $this->accountRepository->persistAccount($accountEntity);

        return $this->createSuccessResponse([
            'message' => 'Account registered successfully.',
            'account' => [
                'accountIdentifier' => $accountEntity->getAccountIdentifier(),
                'firstName' => $accountEntity->getFirstName(),
                'lastName' => $accountEntity->getLastName(),
                'emailAddress' => $accountEntity->getEmailAddress(),
                'roleDesignation' => $accountEntity->getRoleDesignation(),
            ],
        ], 201);
    }
}
