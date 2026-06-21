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
    private bool $equipmentSchemaEnsured = false;

    public function __construct(
        private readonly EquipmentRepository $equipmentRepository,
        private readonly EquipmentAssetIdValidator $equipmentAssetIdValidator,
        private readonly ReservationRepository $reservationRepository,
        private readonly Connection $connection
    ) {
    }

    public function createEquipment(EquipmentCreateRequestDTO $requestDTO): EquipmentResponseDTO
    {
        $this->ensureEquipmentSchemaReady();
        $normalizedPayload = $this->validateAndNormalizePayload($requestDTO);

        $equipmentEntity = new EquipmentEntity();
        $this->hydrateEquipmentEntity($equipmentEntity, $normalizedPayload);
        $this->persistEquipment($equipmentEntity);

        return $this->transformEntityToDTO($equipmentEntity);
    }

    public function updateEquipment(int $equipmentIdentifier, EquipmentCreateRequestDTO $requestDTO): EquipmentResponseDTO
    {
        $this->ensureEquipmentSchemaReady();
        $entity = $this->equipmentRepository->find($equipmentIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Equipment not found: ' . $equipmentIdentifier);
        }

        $normalizedPayload = $this->validateAndNormalizePayload($requestDTO, $equipmentIdentifier);
        $this->hydrateEquipmentEntity($entity, $normalizedPayload);
        $this->persistEquipment($entity);

        return $this->transformEntityToDTO($entity);
    }

    /** @return EquipmentResponseDTO[] */
    public function getAllEquipment(): array
    {
        $this->ensureEquipmentSchemaReady();
        $entities = $this->equipmentRepository->findAllEquipment();
        $dispatchSummary = $this->buildTodayDispatchSummary();

        return array_map(
            fn (EquipmentEntity $entity): EquipmentResponseDTO => $this->transformEntityToDTO($entity, $dispatchSummary),
            $entities
        );
    }

    /** @return EquipmentResponseDTO[] */
    public function getAvailableEquipment(): array
    {
        $this->ensureEquipmentSchemaReady();
        $entities = $this->equipmentRepository->findAvailableEquipment();
        $dispatchSummary = $this->buildTodayDispatchSummary();

        return array_map(
            fn (EquipmentEntity $entity): EquipmentResponseDTO => $this->transformEntityToDTO($entity, $dispatchSummary),
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

        return $this->transformEntityToDTO($entity, $this->buildTodayDispatchSummary());
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

    private function transformEntityToDTO(EquipmentEntity $entity, array $dispatchSummary = []): EquipmentResponseDTO
    {
        $todaySummary = $dispatchSummary[$entity->getEquipmentIdentifier() ?? 0] ?? ['quantity' => 0, 'reservations' => 0];

        return new EquipmentResponseDTO(
            equipmentIdentifier: (int) $entity->getEquipmentIdentifier(),
            equipmentName: $entity->getEquipmentName(),
            equipmentCategory: $entity->getEquipmentCategory(),
            equipmentBrand: $entity->getEquipmentBrand(),
            totalQuantity: $entity->getTotalQuantity(),
            availableQuantity: $entity->getAvailableQuantity(),
            operationalStatus: $entity->getOperationalStatus(),
            equipmentState: $entity->getEquipmentState(),
            description: $entity->getDescription(),
            imageUrl: $entity->getImageUrl(),
            barcode: $entity->getBarcode(),
            assetId: $entity->getAssetId(),
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
            [$assetId, $barcode] = $this->generateInventoryIdentifiers($equipmentCategory);
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
            'availableQuantity' => $availableQuantity,
            'operationalStatus' => $this->normalizeOperationalStatus($operationalStatus),
            'equipmentState' => $this->resolveEquipmentState($operationalStatus),
            'description' => $description,
            'imageUrl' => $imageUrl === '' ? null : $imageUrl,
            'barcode' => $barcode,
            'assetId' => $assetId,
            'photoData' => $photoData === '' ? null : $photoData,
            'photoDisplayMode' => $photoDisplayMode,
            'photoPositionX' => $photoPositionX,
            'photoPositionY' => $photoPositionY,
        ];
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

    private function generateInventoryIdentifiers(string $equipmentCategory): array
    {
        $categoryPrefix = $this->resolveCategoryPrefix($equipmentCategory);
        $existingRecord = $this->equipmentRepository->findHighestGeneratedAssetIdForCategoryPrefix($categoryPrefix);
        $nextSequence = 1;

        if ($existingRecord !== null) {
            $matches = [];
            if (preg_match('/^TR-[A-Z]{3}-(\d{4})$/', $existingRecord->getAssetId(), $matches) === 1) {
                $nextSequence = ((int) $matches[1]) + 1;
            }
        }

        $assetId = sprintf('TR-%s-%04d', $categoryPrefix, $nextSequence);
        $barcode = sprintf('TRBC-%s-%04d', $categoryPrefix, $nextSequence);

        return [$assetId, $barcode];
    }

    private function resolveCategoryPrefix(string $equipmentCategory): string
    {
        $normalizedCategory = strtolower(trim($equipmentCategory));

        return match ($normalizedCategory) {
            'audio / microphone', 'audio' => 'AUD',
            'furniture' => 'FUR',
            'presentation' => 'PRE',
            'accessories' => 'ACC',
            'electrical' => 'ELC',
            'setup' => 'SET',
            'decor' => 'DEC',
            'display' => 'DSP',
            'miscellaneous' => 'MSC',
            default => $this->buildFallbackCategoryPrefix($equipmentCategory),
        };
    }

    private function buildFallbackCategoryPrefix(string $equipmentCategory): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]+/', '', strtoupper($equipmentCategory));
        $normalized = $normalized === null ? '' : $normalized;
        return str_pad(substr($normalized, 0, 3), 3, 'X');
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
