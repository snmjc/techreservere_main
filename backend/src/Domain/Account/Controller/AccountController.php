<?php

namespace App\Domain\Account\Controller;

use App\Domain\Account\DTO\AccountUpdateRequestDTO;
use App\Domain\Account\Service\AccountProfileService;
use App\Domain\Account\Service\AccountUpdateService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/accounts')]
class AccountController extends AbstractController
{
    use JsonResponseTrait;

    private AccountProfileService $accountProfileService;
    private AccountUpdateService $accountUpdateService;

    public function __construct(
        AccountProfileService $accountProfileService,
        AccountUpdateService $accountUpdateService
    ) {
        $this->accountProfileService = $accountProfileService;
        $this->accountUpdateService = $accountUpdateService;
    }

    // ===== AI GENERATED: getMyProfile =====
    // Purpose: Return authenticated user's own profile
    // Inputs: Request (with authenticatedIdentity attribute)
    // Returns: JsonResponse with AccountProfileResponseDTO
    // Flow:
    // 1. Extract email from authenticated identity
    // 2. Call AccountProfileService
    // 3. Return DTO as JSON

    #[Route('/me', name: 'account_get_my_profile', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function getMyProfile(Request $request): JsonResponse
    {
        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity');
        $emailAddress = $authenticatedIdentity['emailAddress'] ?? '';

        $profileDTO = $this->accountProfileService->getAccountProfileByEmail($emailAddress);

        return $this->createSuccessResponse($profileDTO->toResponseArray());
    }

    // ===== AI GENERATED: getAllAccounts =====
    // Purpose: Return all account profiles (Admin only)
    // Inputs: none
    // Returns: JsonResponse with array of AccountProfileResponseDTO
    // Flow:
    // 1. Call AccountProfileService to get all accounts
    // 2. Transform to response array
    // 3. Return JSON

    #[Route('', name: 'account_get_all', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getAllAccounts(): JsonResponse
    {
        $profileDTOs = $this->accountProfileService->getAllAccountProfiles();

        $responseList = [];
        foreach ($profileDTOs as $profileDTO) { // DTO → array loop
            $responseList[] = $profileDTO->toResponseArray();
        }

        return $this->createSuccessResponse(['accounts' => $responseList]);
    }

    // ===== AI GENERATED: getAccountById =====
    // Purpose: Return specific account profile by ID (Admin only)
    // Inputs: accountIdentifier (int)
    // Returns: JsonResponse with AccountProfileResponseDTO
    // Flow:
    // 1. Call AccountProfileService by ID
    // 2. Return DTO as JSON

    #[Route('/{accountIdentifier}', name: 'account_get_by_id', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getAccountById(int $accountIdentifier): JsonResponse
    {
        $profileDTO = $this->accountProfileService->getAccountProfileById($accountIdentifier);

        return $this->createSuccessResponse($profileDTO->toResponseArray());
    }

    // ===== AI GENERATED: updateAccount =====
    // Purpose: Update account profile (Admin: any account, Borrower: own only)
    // Inputs: accountIdentifier (int), Request body
    // Returns: JsonResponse with updated AccountProfileResponseDTO
    // Flow:
    // 1. Parse request body to DTO
    // 2. Call AccountUpdateService
    // 3. Return updated DTO as JSON

    #[Route('/{accountIdentifier}', name: 'account_update', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateAccount(int $accountIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $updateDTO = new AccountUpdateRequestDTO(
            contactNumber: $requestBody['contactNumber'] ?? null,
            roleDesignation: $requestBody['roleDesignation'] ?? null
        );

        $updatedProfile = $this->accountUpdateService->updateAccountProfile($accountIdentifier, $updateDTO);

        return $this->createSuccessResponse($updatedProfile->toResponseArray());
    }
}
