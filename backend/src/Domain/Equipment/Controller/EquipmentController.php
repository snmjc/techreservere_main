<?php

namespace App\Domain\Equipment\Controller;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Account\Service\AdminSecurityConfirmationService;
use App\Domain\Account\Service\AuthenticatedAccountResolver;
use App\Domain\AuditLog\Service\AuditLogRecordService;
use App\Domain\Equipment\DTO\EquipmentCreateRequestDTO;
use App\Domain\Equipment\Service\EquipmentExcelExportService;
use App\Domain\Equipment\Service\EquipmentManagementService;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/equipment')]
class EquipmentController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly EquipmentManagementService $equipmentManagementService,
        private readonly AdminSecurityConfirmationService $adminSecurityConfirmationService,
        private readonly AuthenticatedAccountResolver $authenticatedAccountResolver,
        private readonly EquipmentExcelExportService $equipmentExcelExportService,
        private readonly AuditLogRecordService $auditLogRecordService,
        private readonly AccountRepository $accountRepository
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
            $equipmentRows = $this->applyEquipmentFilters($equipmentRows, $this->extractEquipmentFilters($request));

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

    #[Route('/export/excel', name: 'equipment_export_excel', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN])]
    public function exportEquipmentExcel(Request $request): Response
    {
        try {
            $filterPayload = $this->extractEquipmentFilters($request);
            $equipmentRows = array_map(
                static fn ($equipmentDTO): array => $equipmentDTO->toResponseArray(),
                $this->equipmentManagementService->getAllEquipment()
            );
            $equipmentRows = $this->applyEquipmentFilters($equipmentRows, $filterPayload);

            if ($equipmentRows === []) {
                return $this->createErrorResponse(
                    'EquipmentExportEmpty',
                    'No equipment records match the current filters.',
                    422
                );
            }

            $accountIdentifier = $this->authenticatedAccountResolver->resolveAccountIdentifier($request);
            $account = $this->accountRepository->find($accountIdentifier);
            $actorName = $account === null
                ? 'TechReserve Admin'
                : trim(sprintf('%s %s', $account->getFirstName(), $account->getLastName()));
            $actorName = $actorName !== '' ? $actorName : ($account?->getEmailAddress() ?? 'TechReserve Admin');
            $actorRole = $account?->getRoleDesignation() ?? RoleConstants::ROLE_ADMIN;

            $workbook = $this->equipmentExcelExportService->generateWorkbook(
                $equipmentRows,
                $filterPayload,
                $actorName,
                new \DateTimeImmutable('now')
            );

            $this->auditLogRecordService->recordAuditLog(
                $accountIdentifier,
                'Export Equipment Excel',
                'Equipment',
                null,
                [
                    'filters' => $filterPayload,
                    'exportedRecordCount' => $workbook['exportedRowCount'],
                ],
                [
                    'actorName' => $actorName,
                    'actorRole' => $actorRole,
                    'module' => 'Equipment Inventory',
                    'targetDisplayLabel' => $workbook['fileName'],
                    'reason' => sprintf(
                        'Exported %d equipment record(s) using the active inventory filters.',
                        (int) $workbook['exportedRowCount']
                    ),
                    'ipAddress' => $request->getClientIp(),
                    'deviceMetadata' => (string) $request->headers->get('User-Agent', ''),
                ]
            );

            $response = new Response($workbook['content'], 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $workbook['fileName']),
                'Content-Length' => (string) strlen($workbook['content']),
            ]);

            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Equipment Export - Error [%s]: %s in %s:%d',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));

            return $this->createErrorResponse('EquipmentExportFailed', 'Unable to export equipment records right now.', 500);
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
            $equipmentRecord = $equipmentDTO->toResponseArray();
            $auditActor = $this->resolveAuditActorContext($request);

            $this->auditLogRecordService->recordAuditLog(
                $auditActor['accountIdentifier'],
                'Create Equipment',
                'Equipment',
                isset($equipmentRecord['equipmentIdentifier']) ? (int) $equipmentRecord['equipmentIdentifier'] : null,
                [
                    'createdEquipment' => $equipmentRecord,
                ],
                [
                    'actorName' => $auditActor['actorName'],
                    'actorRole' => $auditActor['actorRole'],
                    'module' => 'Equipment Inventory',
                    'targetDisplayLabel' => (string) ($equipmentRecord['equipmentName'] ?? 'Equipment'),
                    'updatedValue' => $equipmentRecord,
                    'reason' => 'Created a new equipment inventory record.',
                    'ipAddress' => $request->getClientIp(),
                    'deviceMetadata' => (string) $request->headers->get('User-Agent', ''),
                ]
            );

            return $this->createSuccessResponse($equipmentRecord, 201);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('EquipmentValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/{equipmentIdentifier}', name: 'equipment_update', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateEquipment(int $equipmentIdentifier, Request $request): JsonResponse
    {
        try {
            $previousEquipmentRecord = $this->equipmentManagementService->getEquipmentById($equipmentIdentifier)->toResponseArray();
            $equipmentDTO = $this->equipmentManagementService->updateEquipment(
                $equipmentIdentifier,
                $this->buildCreateRequestDTO($request)
            );
            $updatedEquipmentRecord = $equipmentDTO->toResponseArray();
            $auditActor = $this->resolveAuditActorContext($request);

            $this->auditLogRecordService->recordAuditLog(
                $auditActor['accountIdentifier'],
                'Update Equipment',
                'Equipment',
                $equipmentIdentifier,
                [
                    'equipmentIdentifier' => $equipmentIdentifier,
                ],
                [
                    'actorName' => $auditActor['actorName'],
                    'actorRole' => $auditActor['actorRole'],
                    'module' => 'Equipment Inventory',
                    'targetDisplayLabel' => (string) ($updatedEquipmentRecord['equipmentName'] ?? $previousEquipmentRecord['equipmentName'] ?? 'Equipment'),
                    'previousValue' => $previousEquipmentRecord,
                    'updatedValue' => $updatedEquipmentRecord,
                    'reason' => 'Updated an equipment inventory record.',
                    'ipAddress' => $request->getClientIp(),
                    'deviceMetadata' => (string) $request->headers->get('User-Agent', ''),
                ]
            );

            return $this->createSuccessResponse($updatedEquipmentRecord);
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
            $previousEquipmentRecord = $this->equipmentManagementService->getEquipmentById($equipmentIdentifier)->toResponseArray();
            $this->equipmentManagementService->deleteEquipment($equipmentIdentifier);
            $auditActor = $this->resolveAuditActorContext($request);

            $this->auditLogRecordService->recordAuditLog(
                $auditActor['accountIdentifier'],
                'Delete Equipment',
                'Equipment',
                $equipmentIdentifier,
                [
                    'deletedEquipmentIdentifier' => $equipmentIdentifier,
                ],
                [
                    'actorName' => $auditActor['actorName'],
                    'actorRole' => $auditActor['actorRole'],
                    'module' => 'Equipment Inventory',
                    'targetDisplayLabel' => (string) ($previousEquipmentRecord['equipmentName'] ?? 'Equipment'),
                    'previousValue' => $previousEquipmentRecord,
                    'reason' => 'Deleted an equipment inventory record.',
                    'ipAddress' => $request->getClientIp(),
                    'deviceMetadata' => (string) $request->headers->get('User-Agent', ''),
                ]
            );

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

    /**
     * @return array{accountIdentifier: int, actorName: string, actorRole: string}
     */
    private function resolveAuditActorContext(Request $request): array
    {
        $accountIdentifier = $this->authenticatedAccountResolver->resolveAccountIdentifier($request);
        $account = $this->accountRepository->find($accountIdentifier);
        $actorName = $account === null
            ? 'TechReserve Admin'
            : trim(sprintf('%s %s', $account->getFirstName(), $account->getLastName()));
        $actorName = $actorName !== '' ? $actorName : ($account?->getEmailAddress() ?? 'TechReserve Admin');

        return [
            'accountIdentifier' => $accountIdentifier,
            'actorName' => $actorName,
            'actorRole' => $account?->getRoleDesignation() ?? RoleConstants::ROLE_ADMIN,
        ];
    }

    /**
     * @return array{search: string, status: string, category: string, condition: string, storageLocation: string, acquiredStartDate: string, acquiredEndDate: string, datePreset: string, sort: string}
     */
    private function extractEquipmentFilters(Request $request): array
    {
        return [
            'search' => strtolower(trim((string) $request->query->get('search', ''))),
            'status' => trim((string) $request->query->get('status', '')),
            'category' => strtolower(trim((string) $request->query->get('category', ''))),
            'condition' => strtolower(trim((string) $request->query->get('condition', ''))),
            'storageLocation' => strtolower(trim((string) $request->query->get('storageLocation', ''))),
            'acquiredStartDate' => trim((string) $request->query->get('acquiredStartDate', '')),
            'acquiredEndDate' => trim((string) $request->query->get('acquiredEndDate', '')),
            'datePreset' => strtolower(trim((string) $request->query->get('datePreset', ''))),
            'sort' => strtolower(trim((string) $request->query->get('sort', 'name'))),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $equipmentRows
     * @param array{search: string, status: string, category: string, condition: string, storageLocation: string, acquiredStartDate: string, acquiredEndDate: string, datePreset: string, sort: string} $filters
     * @return array<int, array<string, mixed>>
     */
    private function applyEquipmentFilters(array $equipmentRows, array $filters): array
    {
        $search = $filters['search'];
        $status = $filters['status'];
        $category = $filters['category'];
        $condition = $filters['condition'];
        $storageLocation = $filters['storageLocation'];
        $sort = $filters['sort'];
        [$acquiredStartDate, $acquiredEndDate] = $this->resolveAcquiredDateRange(
            $filters['datePreset'],
            $filters['acquiredStartDate'],
            $filters['acquiredEndDate']
        );

        $filteredRows = array_values(array_filter($equipmentRows, function (array $row) use (
            $search,
            $status,
            $category,
            $condition,
            $storageLocation,
            $acquiredStartDate,
            $acquiredEndDate
        ): bool {
            if ($search !== '') {
                $unitText = implode(' ', array_merge(
                    $this->collectUnitFieldValues($row, 'barcode'),
                    $this->collectUnitFieldValues($row, 'assetTag'),
                    $this->collectUnitFieldValues($row, 'serialNumber'),
                    $this->collectUnitFieldValues($row, 'storageLocation')
                ));
                $haystack = strtolower(implode(' ', [
                    (string) ($row['equipmentName'] ?? ''),
                    (string) ($row['equipmentCategory'] ?? ''),
                    (string) ($row['equipmentBrand'] ?? ''),
                    (string) ($row['equipmentModel'] ?? ''),
                    (string) ($row['remarks'] ?? ''),
                    (string) ($row['barcode'] ?? ''),
                    (string) ($row['assetId'] ?? ''),
                    $unitText,
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

            if ($condition !== '' && !$this->rowMatchesUnitField($row, 'conditionStatus', $condition)) {
                return false;
            }

            if ($storageLocation !== '' && !$this->rowMatchesUnitField($row, 'storageLocation', $storageLocation)) {
                return false;
            }

            if (($acquiredStartDate !== null || $acquiredEndDate !== null) && !$this->rowMatchesAcquiredDateRange($row, $acquiredStartDate, $acquiredEndDate)) {
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

    private function rowMatchesUnitField(array $row, string $fieldName, string $needle): bool
    {
        foreach ($this->collectUnitFieldValues($row, $fieldName) as $value) {
            if ($value === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function collectUnitFieldValues(array $row, string $fieldName): array
    {
        $values = [];
        foreach ((array) ($row['units'] ?? []) as $unit) {
            if (!is_array($unit)) {
                continue;
            }

            $value = strtolower(trim((string) ($unit[$fieldName] ?? '')));
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values));
    }

    private function rowMatchesAcquiredDateRange(array $row, ?\DateTimeImmutable $startDate, ?\DateTimeImmutable $endDate): bool
    {
        foreach ((array) ($row['units'] ?? []) as $unit) {
            if (!is_array($unit)) {
                continue;
            }

            $dateValue = trim((string) ($unit['dateAcquired'] ?? ''));
            if ($dateValue === '') {
                continue;
            }

            $acquiredAt = $this->parseDateValue($dateValue);
            if ($acquiredAt === null) {
                continue;
            }

            if ($startDate !== null && $acquiredAt < $startDate) {
                continue;
            }

            if ($endDate !== null && $acquiredAt > $endDate) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @return array{0: ?\DateTimeImmutable, 1: ?\DateTimeImmutable}
     */
    private function resolveAcquiredDateRange(string $datePreset, string $startDate, string $endDate): array
    {
        $today = new \DateTimeImmutable('today');

        return match ($datePreset) {
            'today' => [$today, $today],
            'last-7-days' => [$today->modify('-6 days'), $today],
            'last-30-days' => [$today->modify('-29 days'), $today],
            'this-year' => [new \DateTimeImmutable($today->format('Y-01-01')), new \DateTimeImmutable($today->format('Y-12-31'))],
            default => [$this->parseDateValue($startDate), $this->parseDateValue($endDate)],
        };
    }

    private function parseDateValue(string $value): ?\DateTimeImmutable
    {
        $normalizedValue = trim($value);
        if ($normalizedValue === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable(substr($normalizedValue, 0, 10));
        } catch (\Throwable) {
            return null;
        }
    }
}
