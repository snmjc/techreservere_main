<?php

namespace App\Domain\Equipment\Controller;

use App\Domain\Equipment\DTO\EquipmentCreateRequestDTO;
use App\Domain\Equipment\Service\EquipmentManagementService;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
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

    public function __construct(private readonly EquipmentManagementService $equipmentManagementService)
    {
    }

    #[Route('', name: 'equipment_list_all', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function listAllEquipment(Request $request): JsonResponse
    {
        $resolvedRole = $request->attributes->get('resolvedRole', '');
        $equipmentDTOs = $resolvedRole === RoleConstants::ROLE_BORROWER
            ? $this->equipmentManagementService->getAvailableEquipment()
            : $this->equipmentManagementService->getAllEquipment();

        $responseList = array_map(
            static fn ($equipmentDTO): array => $equipmentDTO->toResponseArray(),
            $equipmentDTOs
        );

        return $this->createSuccessResponse(['equipment' => $responseList]);
    }

    #[Route('/{equipmentIdentifier}', name: 'equipment_get_by_id', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function getEquipmentById(int $equipmentIdentifier): JsonResponse
    {
        try {
            $equipmentDTO = $this->equipmentManagementService->getEquipmentById($equipmentIdentifier);

            return $this->createSuccessResponse($equipmentDTO->toResponseArray());
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('EquipmentNotFound', $exception->getMessage(), 404);
        }
    }

    #[Route('', name: 'equipment_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createEquipment(Request $request): JsonResponse
    {
        try {
            $createDTO = $this->buildCreateRequestDTO($request);
            $createdEquipment = $this->equipmentManagementService->createEquipment($createDTO);

            return $this->createSuccessResponse($createdEquipment->toResponseArray(), 201);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('EquipmentValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/{equipmentIdentifier}', name: 'equipment_update', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateEquipment(int $equipmentIdentifier, Request $request): JsonResponse
    {
        try {
            $updateDTO = $this->buildCreateRequestDTO($request);
            $updatedEquipment = $this->equipmentManagementService->updateEquipment($equipmentIdentifier, $updateDTO);

            return $this->createSuccessResponse($updatedEquipment->toResponseArray());
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('EquipmentNotFound', $exception->getMessage(), 404);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('EquipmentValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/{equipmentIdentifier}', name: 'equipment_delete', methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteEquipment(int $equipmentIdentifier): JsonResponse
    {
        try {
            $this->equipmentManagementService->deleteEquipment($equipmentIdentifier);

            return $this->createSuccessResponse(['message' => 'Equipment deleted successfully.']);
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('EquipmentNotFound', $exception->getMessage(), 404);
        }
    }

    private function buildCreateRequestDTO(Request $request): EquipmentCreateRequestDTO
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];

        return new EquipmentCreateRequestDTO(
            equipmentName: (string)($requestBody['equipmentName'] ?? ''),
            equipmentCategory: (string)($requestBody['equipmentCategory'] ?? $requestBody['categoryName'] ?? ''),
            equipmentBrand: (string)($requestBody['equipmentBrand'] ?? ''),
            availableQuantity: (int)($requestBody['availableQuantity'] ?? $requestBody['totalQuantity'] ?? 0),
            operationalStatus: (string)($requestBody['operationalStatus'] ?? $requestBody['equipmentState'] ?? ''),
            description: $requestBody['description'] ?? $requestBody['scheduleDescription'] ?? null,
            imageUrl: $requestBody['imageUrl'] ?? null,
            barcode: (string)($requestBody['barcode'] ?? ''),
            assetId: (string)($requestBody['assetId'] ?? '')
        );
    }
}
