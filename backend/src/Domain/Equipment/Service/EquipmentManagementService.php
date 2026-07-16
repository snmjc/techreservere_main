<?php

namespace App\Domain\Equipment\Service;

use App\Domain\Equipment\DTO\EquipmentCreateRequestDTO;
use App\Domain\Equipment\DTO\EquipmentResponseDTO;
use App\Domain\Equipment\Entity\EquipmentEntity;
use App\Domain\Equipment\Repository\EquipmentRepository;
use App\Domain\Reservation\Repository\ReservationRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Connection;

class EquipmentManagementService
{
    private const ALLOWED_OPERATIONAL_STATUSES = [
        'Available',
        'Unavailable',
        'Under Maintenance',
        'Retired',
        'Active',
        'Inactive',
        'Maintenance',
    ];

    private const ALLOWED_PHOTO_DISPLAY_MODES = ['contain', 'cover'];
    private const GENERATED_ASSET_ID_PATTERN = '/^F(\d{3})-(\d{3})-(\d{3})$/';
    private const GENERATED_BARCODE_PATTERN = '/^\d{5}$/';
    private bool $equipmentSchemaEnsured = false;

    public function __construct(
        private readonly EquipmentRepository $equipmentRepository,
        private readonly EquipmentAssetIdValidator $equipmentAssetIdValidator,
        private readonly ReservationRepository $reservationRepository,
        private readonly Connection $connection,
        private readonly EquipmentInventoryUnitService $equipmentInventoryUnitService
    ) {
    }

    public function createEquipment(EquipmentCreateRequestDTO $requestDTO): EquipmentResponseDTO
    {
        $this->ensureEquipmentSchemaReady();
        $normalizedPayload = $this->validateAndNormalizePayload($requestDTO);

        $equipmentEntity = new EquipmentEntity();
        $this->hydrateEquipmentEntity($equipmentEntity, $normalizedPayload);
        $this->persistEquipment($equipmentEntity);
        $equipmentIdentifier = (int) $equipmentEntity->getEquipmentIdentifier();
        $this->equipmentInventoryUnitService->updateParentMetadata($equipmentIdentifier, $normalizedPayload);
        $this->saveEquipmentUnits($equipmentIdentifier, $normalizedPayload, $requestDTO);

        $reloadedEntity = $this->equipmentRepository->find($equipmentIdentifier) ?? $equipmentEntity;
        return $this->transformEntityToDTO($reloadedEntity);
    }

    public function updateEquipment(int $equipmentIdentifier, EquipmentCreateRequestDTO $requestDTO): EquipmentResponseDTO
    {
        $this->ensureEquipmentSchemaReady();
        $entity = $this->equipmentRepository->find($equipmentIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Equipment not found: ' . $equipmentIdentifier);
        }

        $normalizedPayload = $this->validateAndNormalizePayload(
            $this->preserveExistingInventoryIdentifiers($requestDTO, $entity),
            $equipmentIdentifier
        );
        $this->hydrateEquipmentEntity($entity, $normalizedPayload);
        $this->persistEquipment($entity);
        $this->equipmentInventoryUnitService->updateParentMetadata($equipmentIdentifier, $normalizedPayload);
        $this->saveEquipmentUnits($equipmentIdentifier, $normalizedPayload, $requestDTO);

        $reloadedEntity = $this->equipmentRepository->find($equipmentIdentifier) ?? $entity;
        return $this->transformEntityToDTO($reloadedEntity);
    }

    /** @return EquipmentResponseDTO[] */
    public function getAllEquipment(): array
    {
        $this->ensureEquipmentSchemaReady();
        $entities = $this->equipmentRepository->findAllEquipment();
        $dispatchSummary = $this->buildTodayDispatchSummary();
        [$countsByEquipment, $unitsByEquipment, $metadataByEquipment] = $this->loadInventoryEnhancements($entities);

        return array_map(
            fn (EquipmentEntity $entity): EquipmentResponseDTO => $this->transformEntityToDTO(
                $entity,
                $dispatchSummary,
                $countsByEquipment[(int) $entity->getEquipmentIdentifier()] ?? null,
                $unitsByEquipment[(int) $entity->getEquipmentIdentifier()] ?? [],
                $metadataByEquipment[(int) $entity->getEquipmentIdentifier()] ?? null
            ),
            $entities
        );
    }

    /** @return EquipmentResponseDTO[] */
    public function getAvailableEquipment(): array
    {
        $this->ensureEquipmentSchemaReady();
        $entities = $this->equipmentRepository->findAvailableEquipment();
        $dispatchSummary = $this->buildTodayDispatchSummary();
        [$countsByEquipment, $unitsByEquipment, $metadataByEquipment] = $this->loadInventoryEnhancements($entities);

        return array_map(
            fn (EquipmentEntity $entity): EquipmentResponseDTO => $this->transformEntityToDTO(
                $entity,
                $dispatchSummary,
                $countsByEquipment[(int) $entity->getEquipmentIdentifier()] ?? null,
                $unitsByEquipment[(int) $entity->getEquipmentIdentifier()] ?? [],
                $metadataByEquipment[(int) $entity->getEquipmentIdentifier()] ?? null
            ),
            $entities
        );
    }

    public function getEquipmentById(int $equipmentIdentifier): EquipmentResponseDTO
    {
        $this->ensureEquipmentSchemaReady();
        $entity = $this->equipmentRepository->find($equipmentIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Equipment not found: ' . $equipmentIdentifier);
        }

        try {
            $derivedCounts = $this->equipmentInventoryUnitService->fetchDerivedCountsByEquipmentIds([$equipmentIdentifier])[$equipmentIdentifier] ?? null;
            $unitRecords = $this->equipmentInventoryUnitService->fetchUnitsByEquipmentIds([$equipmentIdentifier])[$equipmentIdentifier] ?? [];
            $parentMetadata = $this->equipmentInventoryUnitService->fetchParentMetadataByEquipmentIds([$equipmentIdentifier])[$equipmentIdentifier] ?? null;
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Equipment detail enhancement fallback [%s]: %s',
                $exception::class,
                $exception->getMessage()
            ));
            $derivedCounts = null;
            $unitRecords = [];
            $parentMetadata = null;
        }

        return $this->transformEntityToDTO(
            $entity,
            $this->buildTodayDispatchSummary(),
            $derivedCounts,
            $unitRecords,
            $parentMetadata
        );
    }

    public function deleteEquipment(int $equipmentIdentifier): void
    {
        $this->ensureEquipmentSchemaReady();
        $entity = $this->equipmentRepository->find($equipmentIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Equipment not found: ' . $equipmentIdentifier);
        }

        $this->equipmentRepository->removeEquipment($entity);
    }

    private function transformEntityToDTO(
        EquipmentEntity $entity,
        array $dispatchSummary = [],
        ?array $derivedCounts = null,
        array $unitRecords = [],
        ?array $parentMetadata = null
    ): EquipmentResponseDTO
    {
        $todaySummary = $dispatchSummary[$entity->getEquipmentIdentifier() ?? 0] ?? ['quantity' => 0, 'reservations' => 0];
        $derivedCounts ??= [
            'total' => $entity->getTotalQuantity(),
            'available' => $entity->getAvailableQuantity(),
            'reserved' => 0,
            'borrowed' => 0,
            'underMaintenance' => 0,
            'unavailable' => max(0, $entity->getTotalQuantity() - $entity->getAvailableQuantity()),
        ];
        $parentMetadata ??= [
            'equipmentModel' => null,
            'remarks' => null,
            'specifications' => null,
        ];

        return new EquipmentResponseDTO(
            equipmentIdentifier: (int) $entity->getEquipmentIdentifier(),
            equipmentName: $entity->getEquipmentName(),
            equipmentCategory: $entity->getEquipmentCategory(),
            equipmentBrand: $entity->getEquipmentBrand(),
            equipmentModel: $parentMetadata['equipmentModel'] ?? null,
            totalQuantity: (int) ($derivedCounts['total'] ?? $entity->getTotalQuantity()),
            availableQuantity: (int) ($derivedCounts['available'] ?? $entity->getAvailableQuantity()),
            reservedQuantity: (int) ($derivedCounts['reserved'] ?? 0),
            borrowedQuantity: (int) ($derivedCounts['borrowed'] ?? 0),
            underMaintenanceQuantity: (int) ($derivedCounts['underMaintenance'] ?? 0),
            unavailableQuantity: (int) ($derivedCounts['unavailable'] ?? 0),
            operationalStatus: $entity->getOperationalStatus(),
            equipmentState: $entity->getEquipmentState(),
            description: $entity->getDescription(),
            remarks: $parentMetadata['remarks'] ?? null,
            specifications: $parentMetadata['specifications'] ?? null,
            imageUrl: $entity->getImageUrl(),
            barcode: $entity->getBarcode(),
            assetId: $entity->getAssetId(),
            units: $unitRecords,
            photoData: $entity->getPhotoData(),
            photoDisplayMode: $entity->getPhotoDisplayMode(),
            photoPositionX: $entity->getPhotoPositionX(),
            photoPositionY: $entity->getPhotoPositionY(),
            dispatchedTodayQuantity: (int) ($todaySummary['quantity'] ?? 0),
            dispatchedTodayReservationCount: (int) ($todaySummary['reservations'] ?? 0),
            createdTimestamp: $entity->getCreatedTimestamp()->format(\DateTime::ATOM),
            updatedTimestamp: $entity->getUpdatedTimestamp()->format(\DateTime::ATOM)
        );
    }

    private function validateAndNormalizePayload(EquipmentCreateRequestDTO $requestDTO, ?int $currentIdentifier = null): array
    {
        $equipmentName = trim($requestDTO->equipmentName);
        $equipmentCategory = trim($requestDTO->equipmentCategory);
        $equipmentBrand = trim($requestDTO->equipmentBrand);
        $description = trim((string) ($requestDTO->description ?? ''));
        $remarks = trim((string) ($requestDTO->remarks ?? ''));
        $equipmentModel = trim((string) ($requestDTO->equipmentModel ?? ''));
        $imageUrl = trim((string) ($requestDTO->imageUrl ?? ''));
        $barcode = trim($requestDTO->barcode);
        $assetId = strtoupper(trim($requestDTO->assetId));
        $photoData = $requestDTO->photoData === null ? null : trim($requestDTO->photoData);
        $operationalStatus = trim($requestDTO->operationalStatus);
        $availableQuantity = $requestDTO->availableQuantity;
        $photoDisplayMode = strtolower(trim($requestDTO->photoDisplayMode ?: 'contain'));
        $photoPositionX = $this->normalizePhotoPosition($requestDTO->photoPositionX);
        $photoPositionY = $this->normalizePhotoPosition($requestDTO->photoPositionY);

        if (strlen($equipmentName) < 2) {
            throw new DomainValidationException('Equipment name must be at least 2 characters.');
        }

        if ($equipmentCategory === '') {
            throw new DomainValidationException('Equipment type/category is required.');
        }

        if (strlen($equipmentBrand) < 2) {
            throw new DomainValidationException('Equipment brand must be at least 2 characters.');
        }

        if ($availableQuantity <= 0) {
            throw new DomainValidationException('Available quantity must be greater than zero.');
        }

        if ($operationalStatus === '') {
            throw new DomainValidationException('Operational status is required.');
        }

        if (!in_array($operationalStatus, self::ALLOWED_OPERATIONAL_STATUSES, true)) {
            throw new DomainValidationException('Invalid operational status.');
        }

        if ($description === '') {
            throw new DomainValidationException('Description is required.');
        }

        if (!in_array($photoDisplayMode, self::ALLOWED_PHOTO_DISPLAY_MODES, true)) {
            throw new DomainValidationException('Invalid equipment photo display mode.');
        }

        if ($assetId === '' || $barcode === '') {
            [$assetId, $barcode] = $this->generateMissingInventoryIdentifiers($equipmentCategory, $assetId, $barcode);
        }

        if (!$this->equipmentAssetIdValidator->isValid($assetId)) {
            throw new DomainValidationException('Asset ID must follow the TechReserve generated format.');
        }

        if ($photoData !== null && $photoData !== '' && preg_match('/^data:image\/(?:jpeg|jpg|png|webp);base64,[A-Za-z0-9+\/=\r\n]+$/i', $photoData) !== 1) {
            throw new DomainValidationException('Equipment photo must be a valid JPG, PNG, or WebP image.');
        }

        $existingBarcode = $this->equipmentRepository->findOneByBarcode($barcode);
        if ($existingBarcode !== null && $existingBarcode->getEquipmentIdentifier() !== $currentIdentifier) {
            throw new DomainValidationException('Barcode already exists.');
        }

        $existingAssetId = $this->equipmentRepository->findOneByAssetId($assetId);
        if ($existingAssetId !== null && $existingAssetId->getEquipmentIdentifier() !== $currentIdentifier) {
            throw new DomainValidationException('Asset ID already exists.');
        }

        return [
            'equipmentName' => $equipmentName,
            'equipmentCategory' => $equipmentCategory,
            'equipmentBrand' => $equipmentBrand,
            'equipmentModel' => $equipmentModel === '' ? null : $equipmentModel,
            'availableQuantity' => $availableQuantity,
            'operationalStatus' => $this->normalizeOperationalStatus($operationalStatus),
            'equipmentState' => $this->resolveEquipmentState($operationalStatus),
            'description' => $description,
            'remarks' => $remarks === '' ? null : $remarks,
            'specifications' => is_array($requestDTO->specifications) ? $requestDTO->specifications : null,
            'imageUrl' => $imageUrl === '' ? null : $imageUrl,
            'barcode' => $barcode,
            'assetId' => $assetId,
            'photoData' => $photoData === '' ? null : $photoData,
            'photoDisplayMode' => $photoDisplayMode,
            'photoPositionX' => $photoPositionX,
            'photoPositionY' => $photoPositionY,
        ];
    }

    private function preserveExistingInventoryIdentifiers(
        EquipmentCreateRequestDTO $requestDTO,
        EquipmentEntity $existingEquipment
    ): EquipmentCreateRequestDTO {
        $barcode = trim($requestDTO->barcode);
        $assetId = strtoupper(trim($requestDTO->assetId));

        if ($barcode !== '' && $assetId !== '') {
            return $requestDTO;
        }

        return new EquipmentCreateRequestDTO(
            equipmentName: $requestDTO->equipmentName,
            equipmentCategory: $requestDTO->equipmentCategory,
            equipmentBrand: $requestDTO->equipmentBrand,
            availableQuantity: $requestDTO->availableQuantity,
            operationalStatus: $requestDTO->operationalStatus,
            equipmentModel: $requestDTO->equipmentModel,
            description: $requestDTO->description,
            remarks: $requestDTO->remarks,
            specifications: $requestDTO->specifications,
            units: $requestDTO->units,
            actionReason: $requestDTO->actionReason,
            imageUrl: $requestDTO->imageUrl,
            barcode: $barcode === '' ? $existingEquipment->getBarcode() : $barcode,
            assetId: $assetId === '' ? $existingEquipment->getAssetId() : $assetId,
            photoData: $requestDTO->photoData,
            photoDisplayMode: $requestDTO->photoDisplayMode,
            photoPositionX: $requestDTO->photoPositionX,
            photoPositionY: $requestDTO->photoPositionY
        );
    }

    private function hydrateEquipmentEntity(EquipmentEntity $equipmentEntity, array $payload): void
    {
        $equipmentEntity->setEquipmentName($payload['equipmentName']);
        $equipmentEntity->setEquipmentCategory($payload['equipmentCategory']);
        $equipmentEntity->setEquipmentBrand($payload['equipmentBrand']);
        $equipmentEntity->setTotalQuantity($payload['availableQuantity']);
        $equipmentEntity->setAvailableQuantity($payload['availableQuantity']);
        $equipmentEntity->setOperationalStatus($payload['operationalStatus']);
        $equipmentEntity->setEquipmentState($payload['equipmentState']);
        $equipmentEntity->setDescription($payload['description']);
        $equipmentEntity->setImageUrl($payload['imageUrl']);
        $equipmentEntity->setBarcode($payload['barcode']);
        $equipmentEntity->setAssetId($payload['assetId']);
        $equipmentEntity->setPhotoData($payload['photoData']);
        $equipmentEntity->setPhotoDisplayMode($payload['photoDisplayMode']);
        $equipmentEntity->setPhotoPositionX($payload['photoPositionX']);
        $equipmentEntity->setPhotoPositionY($payload['photoPositionY']);
    }

    private function persistEquipment(EquipmentEntity $equipmentEntity): void
    {
        try {
            $this->equipmentRepository->persistEquipment($equipmentEntity);
        } catch (UniqueConstraintViolationException) {
            throw new DomainValidationException('Barcode or Asset ID already exists.');
        }
    }

    private function normalizeOperationalStatus(string $operationalStatus): string
    {
        return match ($operationalStatus) {
            'Active' => 'Available',
            'Inactive' => 'Unavailable',
            'Maintenance' => 'Under Maintenance',
            default => $operationalStatus,
        };
    }

    private function resolveEquipmentState(string $operationalStatus): string
    {
        return match ($operationalStatus) {
            'Available', 'Active' => 'Available',
            'Under Maintenance', 'Maintenance' => 'Under Maintenance',
            'Retired' => 'Retired',
            default => 'Unavailable',
        };
    }

    private function ensureEquipmentSchemaReady(): void
    {
        if ($this->equipmentSchemaEnsured) {
            return;
        }

        try {
            $this->equipmentInventoryUnitService->ensureSchemaReady();
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Equipment schema enhancement fallback [%s]: %s',
                $exception::class,
                $exception->getMessage()
            ));
        }

        $columns = $this->connection->fetchAllAssociative(
            "SELECT column_name
             FROM information_schema.columns
             WHERE table_schema = CURRENT_SCHEMA()
               AND table_name = 'equipment'"
        );

        if ($columns === []) {
            $this->equipmentSchemaEnsured = true;
            return;
        }

        $columnNames = array_map(
            static fn (array $column): string => (string) ($column['column_name'] ?? ''),
            $columns
        );

        if (!in_array('photo_display_mode', $columnNames, true)) {
            $this->connection->executeStatement("ALTER TABLE equipment ADD COLUMN photo_display_mode VARCHAR(20) NOT NULL DEFAULT 'contain'");
        }

        if (!in_array('photo_position_x', $columnNames, true)) {
            $this->connection->executeStatement('ALTER TABLE equipment ADD COLUMN photo_position_x INT NOT NULL DEFAULT 50');
        }

        if (!in_array('photo_position_y', $columnNames, true)) {
            $this->connection->executeStatement('ALTER TABLE equipment ADD COLUMN photo_position_y INT NOT NULL DEFAULT 50');
        }

        $this->equipmentSchemaEnsured = true;
    }

    private function loadInventoryEnhancements(array $entities): array
    {
        $equipmentIdentifiers = array_map(static fn (EquipmentEntity $entity): int => (int) $entity->getEquipmentIdentifier(), $entities);

        try {
            return [
                $this->equipmentInventoryUnitService->fetchDerivedCountsByEquipmentIds($equipmentIdentifiers),
                $this->equipmentInventoryUnitService->fetchUnitsByEquipmentIds($equipmentIdentifiers),
                $this->equipmentInventoryUnitService->fetchParentMetadataByEquipmentIds($equipmentIdentifiers),
            ];
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Equipment enhancement fallback [%s]: %s',
                $exception::class,
                $exception->getMessage()
            ));

            return [[], [], []];
        }
    }

    private function saveEquipmentUnits(int $equipmentIdentifier, array $normalizedPayload, EquipmentCreateRequestDTO $requestDTO): void
    {
        $unitRecords = is_array($requestDTO->units) ? $requestDTO->units : [];

        if ($unitRecords === []) {
            $unitRecords = [];
            for ($index = 0; $index < max(1, (int) $normalizedPayload['availableQuantity']); $index++) {
                $unitRecords[] = [
                    'barcode' => $index === 0 ? $normalizedPayload['barcode'] : null,
                    'assetTag' => $index === 0 ? $normalizedPayload['assetId'] : null,
                    'serialNumber' => $index === 0 ? $normalizedPayload['assetId'] : null,
                    'conditionStatus' => 'Good',
                    'availabilityStatus' => $normalizedPayload['operationalStatus'],
                    'remarks' => $normalizedPayload['remarks'] ?? null,
                    'specifications' => $normalizedPayload['specifications'] ?? null,
                    'maintenanceState' => $normalizedPayload['equipmentState'],
                ];
            }
        }

        try {
            $this->equipmentInventoryUnitService->saveUnitsForEquipment(
                $equipmentIdentifier,
                (string) ($normalizedPayload['equipmentCategory'] ?? ''),
                $unitRecords,
                [
                    'availabilityStatus' => $normalizedPayload['operationalStatus'] ?? 'Available',
                    'remarks' => $normalizedPayload['remarks'] ?? null,
                ]
            );
        } catch (\InvalidArgumentException $exception) {
            throw new DomainValidationException($exception->getMessage());
        }
    }

    private function generateMissingInventoryIdentifiers(string $equipmentCategory, string $assetId, string $barcode): array
    {
        $existingEquipmentRecords = $this->equipmentRepository->findAllEquipment();

        if ($assetId === '' && $barcode === '') {
            return $this->generateUniqueInventoryPair($equipmentCategory, $existingEquipmentRecords);
        }

        if ($assetId === '') {
            $assetId = $this->generateUniqueAssetId($equipmentCategory, $existingEquipmentRecords);
        }

        if ($barcode === '') {
            $barcode = $this->generateUniqueBarcode($equipmentCategory, $existingEquipmentRecords);
        }

        return [$assetId, $barcode];
    }

    /**
     * @param EquipmentEntity[] $existingEquipmentRecords
     */
    private function generateUniqueInventoryPair(string $equipmentCategory, array $existingEquipmentRecords): array
    {
        $assetCategoryPrefix = $this->resolveAssetCategoryPrefix($equipmentCategory);
        $barcodeCategoryPrefix = $this->resolveBarcodeCategoryPrefix($assetCategoryPrefix);
        $existingAssetIds = $this->collectExistingAssetIds($existingEquipmentRecords);
        $existingBarcodes = $this->collectExistingBarcodes($existingEquipmentRecords);
        $nextSequence = $this->resolveNextCategorySequence(
            $existingEquipmentRecords,
            $assetCategoryPrefix,
            $barcodeCategoryPrefix
        );

        for ($sequence = $nextSequence; $sequence <= 999; $sequence++) {
            $assetId = $this->formatAssetIdSequence($assetCategoryPrefix, $sequence);
            $barcode = $this->formatBarcodeSequence($barcodeCategoryPrefix, $sequence);
            if (!isset($existingAssetIds[$assetId]) && !isset($existingBarcodes[$barcode])) {
                return [$assetId, $barcode];
            }
        }

        throw new DomainValidationException('Unable to generate unique equipment identifiers for this category.');
    }

    /**
     * @param EquipmentEntity[] $existingEquipmentRecords
     */
    private function generateUniqueAssetId(string $equipmentCategory, array $existingEquipmentRecords): string
    {
        $assetCategoryPrefix = $this->resolveAssetCategoryPrefix($equipmentCategory);
        $barcodeCategoryPrefix = $this->resolveBarcodeCategoryPrefix($assetCategoryPrefix);
        $existingAssetIds = $this->collectExistingAssetIds($existingEquipmentRecords);
        $nextSequence = $this->resolveNextCategorySequence(
            $existingEquipmentRecords,
            $assetCategoryPrefix,
            $barcodeCategoryPrefix
        );

        for ($sequence = $nextSequence; $sequence <= 999; $sequence++) {
            $assetId = $this->formatAssetIdSequence($assetCategoryPrefix, $sequence);
            if (!isset($existingAssetIds[$assetId])) {
                return $assetId;
            }
        }

        throw new DomainValidationException('Unable to generate a unique Asset ID for this category.');
    }

    /**
     * @param EquipmentEntity[] $existingEquipmentRecords
     */
    private function generateUniqueBarcode(string $equipmentCategory, array $existingEquipmentRecords): string
    {
        $assetCategoryPrefix = $this->resolveAssetCategoryPrefix($equipmentCategory);
        $barcodeCategoryPrefix = $this->resolveBarcodeCategoryPrefix($assetCategoryPrefix);
        $existingBarcodes = $this->collectExistingBarcodes($existingEquipmentRecords);
        $nextSequence = $this->resolveNextCategorySequence(
            $existingEquipmentRecords,
            $assetCategoryPrefix,
            $barcodeCategoryPrefix
        );

        for ($sequence = $nextSequence; $sequence <= 999; $sequence++) {
            $barcode = $this->formatBarcodeSequence($barcodeCategoryPrefix, $sequence);
            if (!isset($existingBarcodes[$barcode])) {
                return $barcode;
            }
        }

        throw new DomainValidationException('Unable to generate a unique QR code for this category.');
    }

    /**
     * @param EquipmentEntity[] $existingEquipmentRecords
     */
    private function resolveNextCategorySequence(
        array $existingEquipmentRecords,
        int $assetCategoryPrefix,
        int $barcodeCategoryPrefix
    ): int {
        $highestSequence = 0;

        foreach ($existingEquipmentRecords as $equipmentRecord) {
            $assetMatches = [];
            $assetId = strtoupper(trim($equipmentRecord->getAssetId()));
            if (preg_match(self::GENERATED_ASSET_ID_PATTERN, $assetId, $assetMatches) === 1
                && (int) $assetMatches[1] === $assetCategoryPrefix) {
                $highestSequence = max($highestSequence, (int) ($assetMatches[2] . $assetMatches[3]));
            }

            $barcode = trim($equipmentRecord->getBarcode());
            if (preg_match(self::GENERATED_BARCODE_PATTERN, $barcode) === 1
                && substr($barcode, 0, 2) === sprintf('%02d', $barcodeCategoryPrefix)) {
                $highestSequence = max($highestSequence, (int) substr($barcode, 2, 3));
            }
        }

        return $highestSequence + 1;
    }

    /**
     * @param EquipmentEntity[] $existingEquipmentRecords
     */
    private function collectExistingAssetIds(array $existingEquipmentRecords): array
    {
        $existingAssetIds = [];

        foreach ($existingEquipmentRecords as $equipmentRecord) {
            $assetId = strtoupper(trim($equipmentRecord->getAssetId()));
            if ($assetId !== '') {
                $existingAssetIds[$assetId] = true;
            }
        }

        return $existingAssetIds;
    }

    /**
     * @param EquipmentEntity[] $existingEquipmentRecords
     */
    private function collectExistingBarcodes(array $existingEquipmentRecords): array
    {
        $existingBarcodes = [];

        foreach ($existingEquipmentRecords as $equipmentRecord) {
            $barcode = trim($equipmentRecord->getBarcode());
            if ($barcode !== '') {
                $existingBarcodes[$barcode] = true;
            }
        }

        return $existingBarcodes;
    }

    private function formatAssetIdSequence(int $assetCategoryPrefix, int $sequence): string
    {
        $sequenceDigits = str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        return sprintf(
            'F%s-%s-%s',
            str_pad((string) $assetCategoryPrefix, 3, '0', STR_PAD_LEFT),
            substr($sequenceDigits, 0, 3),
            substr($sequenceDigits, 3, 3)
        );
    }

    private function formatBarcodeSequence(int $barcodeCategoryPrefix, int $sequence): string
    {
        return sprintf('%02d%03d', $barcodeCategoryPrefix, $sequence);
    }

    private function resolveAssetCategoryPrefix(string $equipmentCategory): int
    {
        $normalizedCategory = strtolower(trim($equipmentCategory));

        return match ($normalizedCategory) {
            'audio / microphone', 'audio' => 100,
            'furniture' => 200,
            'presentation' => 300,
            'accessories' => 400,
            'electrical' => 500,
            'setup' => 600,
            'decor' => 700,
            'display' => 800,
            'miscellaneous' => 900,
            default => $this->buildFallbackAssetCategoryPrefix($equipmentCategory),
        };
    }

    private function resolveBarcodeCategoryPrefix(int $assetCategoryPrefix): int
    {
        return max(10, min(99, intdiv($assetCategoryPrefix, 10)));
    }

    private function buildFallbackAssetCategoryPrefix(string $equipmentCategory): int
    {
        $normalizedCategory = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $equipmentCategory) ?? '');
        if ($normalizedCategory === '') {
            return 990;
        }

        $firstCharacter = $normalizedCategory[0];
        $letterOffset = ctype_alpha($firstCharacter) ? ord($firstCharacter) - ord('A') + 1 : (int) $firstCharacter;
        return 900 + max(1, min(89, $letterOffset));
    }

    private function normalizePhotoPosition(int $position): int
    {
        return max(0, min(100, $position));
    }

    private function buildTodayDispatchSummary(): array
    {
        $todayStart = new \DateTimeImmutable('today');
        $todayEnd = $todayStart->setTime(23, 59, 59);
        $reservations = $this->reservationRepository->findAllReservations();
        $summary = [];

        foreach ($reservations as $reservation) {
            $status = $reservation->getCurrentStatus();
            if (!in_array($status, ['Prepared', 'Deployed', 'Active'], true)) {
                continue;
            }

            $eventDateTime = $reservation->getEventDateTime();
            if ($eventDateTime === null) {
                continue;
            }

            $endDateTime = $reservation->getEndDateTime() ?? $eventDateTime;
            if ($eventDateTime > $todayEnd || $endDateTime < $todayStart) {
                continue;
            }

            $countedEquipmentIdentifiers = [];
            foreach ($reservation->getRequestedEquipmentList() as $equipmentItem) {
                $equipmentIdentifier = (int) ($equipmentItem['equipmentIdentifier'] ?? 0);
                if ($equipmentIdentifier <= 0) {
                    continue;
                }

                if (!isset($summary[$equipmentIdentifier])) {
                    $summary[$equipmentIdentifier] = [
                        'quantity' => 0,
                        'reservations' => 0,
                    ];
                }

                $summary[$equipmentIdentifier]['quantity'] += max(0, (int) ($equipmentItem['quantity'] ?? 0));
                if (!in_array($equipmentIdentifier, $countedEquipmentIdentifiers, true)) {
                    $summary[$equipmentIdentifier]['reservations']++;
                    $countedEquipmentIdentifiers[] = $equipmentIdentifier;
                }
            }
        }

        return $summary;
    }
}
