<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Entity\AccountEntity;
use App\Shared\Traits\JsonResponseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users')]
class UserRegistrationController extends AbstractController
{
    use JsonResponseTrait;

    private AccountRepository $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    #[Route('/register', name: 'user_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $clerkUserId = trim($requestBody['clerkUserId'] ?? '');
        $firstName = trim($requestBody['firstName'] ?? '');
        $lastName = trim($requestBody['lastName'] ?? '');
        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $role = trim($requestBody['role'] ?? 'ROLE_BORROWER');
        $contactNumber = trim($requestBody['contactNumber'] ?? '');
        $invitedBy = $requestBody['invitedBy'] ?? null;

        if (empty($clerkUserId) || empty($firstName) || empty($lastName) || empty($emailAddress)) {
            return $this->createErrorResponse(
                'ValidationError',
                'clerkUserId, firstName, lastName, and emailAddress are required.',
                400
            );
        }

        // Check if user already exists
        $existingAccount = $this->accountRepository->findOneByClerkUserId($clerkUserId);
        if ($existingAccount !== null) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                'An account with this Clerk user ID already exists.',
                409
            );
        }

        $existingEmailAccount = $this->accountRepository->findOneByEmailAddress($emailAddress);
        if ($existingEmailAccount !== null) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                'An account with this email address already exists.',
                409
            );
        }

        // Create new account
        $account = new AccountEntity();
        $account->setClerkUserId($clerkUserId);
        $account->setFirstName($firstName);
        $account->setLastName($lastName);
        $account->setEmailAddress($emailAddress);
        $account->setRoleDesignation($role);
        $account->setContactNumber($contactNumber);
        $account->setIsActive(true);

        // Set approval status based on whether it's an invitation
        if ($invitedBy) {
            $account->setStatus('approved');
            $account->setIsApproved(true);
            
            // Set invited by relationship
            $inviterAccount = $this->accountRepository->find($invitedBy);
            if ($inviterAccount) {
                $account->setInvitedBy($inviterAccount);
            }
        } else {
            $account->setStatus('pending');
            $account->setIsApproved(false);
        }

        $this->accountRepository->persistAccount($account);

        return $this->createSuccessResponse([
            'message' => 'Account registered successfully.',
            'account' => [
                'accountIdentifier' => $account->getAccountIdentifier(),
                'clerkUserId' => $account->getClerkUserId(),
                'firstName' => $account->getFirstName(),
                'lastName' => $account->getLastName(),
                'emailAddress' => $account->getEmailAddress(),
                'roleDesignation' => $account->getRoleDesignation(),
                'status' => $account->getStatus(),
                'isApproved' => $account->getIsApproved(),
            ],
        ], 201);
    }

    #[Route('/pending', name: 'list_pending_users', methods: ['GET'])]
    public function listPendingUsers(): JsonResponse
    {
        // Get all pending users
        $pendingUsers = $this->accountRepository->findBy([
            'status' => 'pending',
            'isApproved' => false
        ]);

        $users = [];
        foreach ($pendingUsers as $user) {
            $users[] = [
                'accountIdentifier' => $user->getAccountIdentifier(),
                'clerkUserId' => $user->getClerkUserId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'emailAddress' => $user->getEmailAddress(),
                'roleDesignation' => $user->getRoleDesignation(),
                'contactNumber' => $user->getContactNumber(),
                'status' => $user->getStatus(),
                'isApproved' => $user->getIsApproved(),
                'createdTimestamp' => $user->getCreatedTimestamp()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->createSuccessResponse([
            'count' => count($users),
            'users' => $users,
        ]);
    }

    #[Route('/{accountIdentifier}/approve', name: 'approve_user', methods: ['POST'])]
    public function approveUser(int $accountIdentifier): JsonResponse
    {
        $account = $this->accountRepository->find($accountIdentifier);

        if ($account === null) {
            return $this->createErrorResponse(
                'UserNotFound',
                'User not found.',
                404
            );
        }

        $account->setStatus('approved');
        $account->setIsApproved(true);
        $this->accountRepository->persistAccount($account);

        return $this->createSuccessResponse([
            'message' => 'User approved successfully.',
            'account' => [
                'accountIdentifier' => $account->getAccountIdentifier(),
                'status' => $account->getStatus(),
                'isApproved' => $account->getIsApproved(),
            ],
        ]);
    }

    #[Route('/{accountIdentifier}/reject', name: 'reject_user', methods: ['POST'])]
    public function rejectUser(int $accountIdentifier): JsonResponse
    {
        $account = $this->accountRepository->find($accountIdentifier);

        if ($account === null) {
            return $this->createErrorResponse(
                'UserNotFound',
                'User not found.',
                404
            );
        }

        $account->setStatus('rejected');
        $account->setIsApproved(false);
        $this->accountRepository->persistAccount($account);

        return $this->createSuccessResponse([
            'message' => 'User rejected successfully.',
            'account' => [
                'accountIdentifier' => $account->getAccountIdentifier(),
                'status' => $account->getStatus(),
                'isApproved' => $account->getIsApproved(),
            ],
        ]);
    }

    #[Route('/invite', name: 'invite_user', methods: ['POST'])]
    public function inviteUser(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $emailAddress = trim($requestBody['emailAddress'] ?? '');
        $role = trim($requestBody['role'] ?? 'ROLE_BORROWER');
        $invitedBy = $requestBody['invitedBy'] ?? null;

        if (empty($emailAddress)) {
            return $this->createErrorResponse(
                'ValidationError',
                'emailAddress is required.',
                400
            );
        }

        // Check if user already exists
        $existingAccount = $this->accountRepository->findOneByEmailAddress($emailAddress);
        if ($existingAccount !== null) {
            return $this->createErrorResponse(
                'DuplicateAccount',
                'An account with this email address already exists.',
                409
            );
        }

        // In a real implementation, you would:
        // 1. Generate an invitation token
        // 2. Send an email with Clerk invitation link
        // 3. Store the invitation in the database

        // For now, return success with invitation details
        return $this->createSuccessResponse([
            'message' => 'Invitation sent successfully.',
            'invitation' => [
                'emailAddress' => $emailAddress,
                'role' => $role,
                'invitedBy' => $invitedBy,
                'status' => 'invited',
            ],
        ]);
    }
}
