<?php

namespace App\Domain\Equipment\Controller;

use App\Domain\Equipment\DTO\EquipmentCreateRequestDTO;
use App\Domain\Equipment\Service\EquipmentManagementService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/equipment')]
class EquipmentController extends AbstractController
{
    use JsonResponseTrait;

    private EquipmentManagementService $equipmentManagementService;

    public function __construct(EquipmentManagementService $equipmentManagementService)
    {
        $this->equipmentManagementService = $equipmentManagementService;
    }

    // ===== AI GENERATED: listAllEquipment =====
    // Purpose: Return all equipment (Admin: full list, Borrower: available only)
    // Inputs: Request
    // Returns: JsonResponse
    // Flow:
    // 1. Check role from request attributes
    // 2. Return all or available based on role

    #[Route('', name: 'equipment_list_all', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function listAllEquipment(Request $request): JsonResponse
    {
        $resolvedRole = $request->attributes->get('resolvedRole', '');

        if ($resolvedRole === RoleConstants::ROLE_BORROWER) {
            $equipmentDTOs = $this->equipmentManagementService->getAvailableEquipment();
        } else {
            $equipmentDTOs = $this->equipmentManagementService->getAllEquipment();
        }

        $responseList = [];
        foreach ($equipmentDTOs as $equipmentDTO) { // DTO → array loop
            $responseList[] = $equipmentDTO->toResponseArray();
        }

        return $this->createSuccessResponse(['equipment' => $responseList]);
    }

    // ===== AI GENERATED: getEquipmentById =====
    // Purpose: Return single equipment by ID
    // Inputs: equipmentIdentifier (int)
    // Returns: JsonResponse
    // Flow:
    // 1. Call service by ID
    // 2. Return DTO

    #[Route('/{equipmentIdentifier}', name: 'equipment_get_by_id', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function getEquipmentById(int $equipmentIdentifier): JsonResponse
    {
        $equipmentDTO = $this->equipmentManagementService->getEquipmentById($equipmentIdentifier);
        return $this->createSuccessResponse($equipmentDTO->toResponseArray());
    }

    // ===== AI GENERATED: createEquipment =====
    // Purpose: Create new equipment record (Admin only)
    // Inputs: Request body
    // Returns: JsonResponse
    // Flow:
    // 1. Parse request body to DTO
    // 2. Call service
    // 3. Return created DTO

    #[Route('', name: 'equipment_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createEquipment(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        $createDTO = new EquipmentCreateRequestDTO(
            equipmentName: $requestBody['equipmentName'] ?? '',
            categoryName: $requestBody['categoryName'] ?? '',
            totalQuantity: (int)($requestBody['totalQuantity'] ?? 0),
            operationalStatus: $requestBody['operationalStatus'] ?? 'Active',
            scheduleDescription: $requestBody['scheduleDescription'] ?? null
        );

        $createdEquipment = $this->equipmentManagementService->createEquipment($createDTO);

        return $this->createSuccessResponse($createdEquipment->toResponseArray(), 201);
    }
}
