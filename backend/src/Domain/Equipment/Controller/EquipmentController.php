<?php

namespace App\Domain\Equipment\Controller;

use App\Domain\Account\Service\AdminSecurityConfirmationService;
use App\Domain\Account\Service\AuthenticatedAccountResolver;
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

    public function __construct(
        private readonly EquipmentManagementService $equipmentManagementService,
        private readonly AdminSecurityConfirmationService $adminSecurityConfirmationService,
        private readonly AuthenticatedAccountResolver $authenticatedAccountResolver
    ) {
    }

    #[Route('', name: 'equipment_list_all', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_BORROWER, RoleConstants::ROLE_DEVELOPER])]
    public function listAllEquipment(Request $request): JsonResponse
    {
        try {
            $resolvedRole = $request->attributes->get('resolvedRole', '');
            $equipmentDTOs = $resolvedRole === RoleConstants::ROLE_BORROWER
                ? $this->equipmentManagementService->getAvailableEquipment()
                : $this->equipmentManagementService->getAllEquipment();
            $equipmentRows = array_map(
                static fn ($equipmentDTO): array => $equipmentDTO->toResponseArray(),
                $equipmentDTOs
            );
            $equipmentRows = $this->applyEquipmentFilters($equipmentRows, $request);

            return $this->createSuccessResponse([
                'equipment' => $equipmentRows,
                'summary' => [
                    'total' => count($equipmentRows),
                    'available' => count(array_filter($equipmentRows, static fn (array $row): bool => (int) ($row['availableQuantity'] ?? 0) > 0)),
                    'reserved' => array_sum(array_map(static fn (array $row): int => (int) ($row['reservedQuantity'] ?? 0), $equipmentRows)),
                    'underMaintenance' => array_sum(array_map(static fn (array $row): int => (int) ($row['underMaintenanceQuantity'] ?? 0), $equipmentRows)),
                ],
            ]);
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Equipment List - Error [%s]: %s in %s:%d',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));

            return $this->createErrorResponse('EquipmentListFailed', 'Unable to load equipment records at this time.', 500);
        }
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
            $equipmentDTO = $this->equipmentManagementService->createEquipment($this->buildCreateRequestDTO($request));

            return $this->createSuccessResponse($equipmentDTO->toResponseArray(), 201);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('EquipmentValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/{equipmentIdentifier}', name: 'equipment_update', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateEquipment(int $equipmentIdentifier, Request $request): JsonResponse
    {
        try {
            $equipmentDTO = $this->equipmentManagementService->updateEquipment(
                $equipmentIdentifier,
                $this->buildCreateRequestDTO($request)
            );

            return $this->createSuccessResponse($equipmentDTO->toResponseArray());
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('EquipmentNotFound', $exception->getMessage(), 404);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('EquipmentValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/{equipmentIdentifier}', name: 'equipment_delete', methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteEquipment(int $equipmentIdentifier, Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true) ?? [];
        $securityError = $this->adminSecurityConfirmationService->validateAdminCredentials(
            $this->authenticatedAccountResolver->resolveAccountIdentifier($request),
            (string) ($requestBody['confirmedAdminEmail'] ?? ''),
            (string) ($requestBody['confirmedAdminPassword'] ?? ''),
            'deleting'
        );

        if ($securityError !== null) {
            return $this->createErrorResponse('SecurityConfirmationFailed', $securityError, 422);
        }

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
            equipmentName: (string) ($requestBody['equipmentName'] ?? ''),
            equipmentCategory: (string) ($requestBody['equipmentCategory'] ?? $requestBody['categoryName'] ?? ''),
            equipmentBrand: (string) ($requestBody['equipmentBrand'] ?? ''),
            availableQuantity: (int) ($requestBody['availableQuantity'] ?? $requestBody['totalQuantity'] ?? 0),
            operationalStatus: (string) ($requestBody['operationalStatus'] ?? $requestBody['equipmentState'] ?? ''),
            equipmentModel: $requestBody['equipmentModel'] ?? null,
            description: $requestBody['description'] ?? $requestBody['scheduleDescription'] ?? null,
            remarks: $requestBody['remarks'] ?? null,
            specifications: is_array($requestBody['specifications'] ?? null) ? $requestBody['specifications'] : null,
            units: is_array($requestBody['units'] ?? null) ? $requestBody['units'] : [],
            actionReason: $requestBody['actionReason'] ?? null,
            imageUrl: $requestBody['imageUrl'] ?? null,
            barcode: (string) ($requestBody['barcode'] ?? ''),
            assetId: (string) ($requestBody['assetId'] ?? $requestBody['serialNumber'] ?? ''),
            photoData: $requestBody['photoData'] ?? null,
            photoDisplayMode: (string) ($requestBody['photoDisplayMode'] ?? 'contain'),
            photoPositionX: (int) ($requestBody['photoPositionX'] ?? 50),
            photoPositionY: (int) ($requestBody['photoPositionY'] ?? 50)
        );
    }

    private function applyEquipmentFilters(array $equipmentRows, Request $request): array
    {
        $search = strtolower(trim((string) $request->query->get('search', '')));
        $status = trim((string) $request->query->get('status', ''));
        $category = strtolower(trim((string) $request->query->get('category', '')));
        $sort = strtolower(trim((string) $request->query->get('sort', 'name')));

        $filteredRows = array_values(array_filter($equipmentRows, static function (array $row) use ($search, $status, $category): bool {
            if ($search !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($row['equipmentName'] ?? ''),
                    (string) ($row['equipmentCategory'] ?? ''),
                    (string) ($row['equipmentBrand'] ?? ''),
                    (string) ($row['equipmentModel'] ?? ''),
                    (string) ($row['remarks'] ?? ''),
                ]));
                if (!str_contains($haystack, $search)) {
                    return false;
                }
            }

            if ($status !== '' && strcasecmp((string) ($row['operationalStatus'] ?? $row['equipmentState'] ?? ''), $status) !== 0) {
                return false;
            }

            if ($category !== '' && strtolower((string) ($row['equipmentCategory'] ?? '')) !== $category) {
                return false;
            }

            return true;
        }));

        usort($filteredRows, static function (array $left, array $right) use ($sort): int {
            if ($sort === 'updated') {
                return strtotime((string) ($right['updatedTimestamp'] ?? $right['createdTimestamp'] ?? '')) <=> strtotime((string) ($left['updatedTimestamp'] ?? $left['createdTimestamp'] ?? ''));
            }

            return strcasecmp((string) ($left['equipmentName'] ?? ''), (string) ($right['equipmentName'] ?? ''));
        });

        return $filteredRows;
    }
}
