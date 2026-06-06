<?php

namespace App\Domain\Equipment\Service;

use App\Domain\Equipment\DTO\EquipmentCreateRequestDTO;
use App\Domain\Equipment\DTO\EquipmentResponseDTO;
use App\Domain\Equipment\Entity\EquipmentEntity;
use App\Domain\Equipment\Repository\EquipmentRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

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

    public function __construct(
        private readonly EquipmentRepository $equipmentRepository,
        private readonly EquipmentAssetIdValidator $equipmentAssetIdValidator
    ) {
    }

    public function createEquipment(EquipmentCreateRequestDTO $requestDTO): EquipmentResponseDTO
    {
        $normalizedPayload = $this->validateAndNormalizePayload($requestDTO);

        $equipmentEntity = new EquipmentEntity();
        $this->hydrateEquipmentEntity($equipmentEntity, $normalizedPayload);
        $this->persistEquipment($equipmentEntity);

        return $this->transformEntityToDTO($equipmentEntity);
    }

    public function updateEquipment(int $equipmentIdentifier, EquipmentCreateRequestDTO $requestDTO): EquipmentResponseDTO
    {
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
        $entities = $this->equipmentRepository->findAllEquipment();

        return array_map(
            fn (EquipmentEntity $entity): EquipmentResponseDTO => $this->transformEntityToDTO($entity),
            $entities
        );
    }

    /** @return EquipmentResponseDTO[] */
    public function getAvailableEquipment(): array
    {
        $entities = $this->equipmentRepository->findAvailableEquipment();

        return array_map(
            fn (EquipmentEntity $entity): EquipmentResponseDTO => $this->transformEntityToDTO($entity),
            $entities
        );
    }

    public function getEquipmentById(int $equipmentIdentifier): EquipmentResponseDTO
    {
        $entity = $this->equipmentRepository->find($equipmentIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Equipment not found: ' . $equipmentIdentifier);
        }

        return $this->transformEntityToDTO($entity);
    }

    public function deleteEquipment(int $equipmentIdentifier): void
    {
        $entity = $this->equipmentRepository->find($equipmentIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Equipment not found: ' . $equipmentIdentifier);
        }

        $this->equipmentRepository->removeEquipment($entity);
    }

    private function transformEntityToDTO(EquipmentEntity $entity): EquipmentResponseDTO
    {
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

        if ($barcode === '') {
            throw new DomainValidationException('Barcode is required.');
        }

        if ($assetId === '') {
            throw new DomainValidationException('Asset ID is required.');
        }

        if (!$this->equipmentAssetIdValidator->isValid($assetId)) {
            throw new DomainValidationException('Asset ID must follow the format F123-456-789.');
        }

        if ($photoData !== null && $photoData !== '' && preg_match('/^data:image\/jpeg;base64,[A-Za-z0-9+\/=\r\n]+$/', $photoData) !== 1) {
            throw new DomainValidationException('Equipment photo must be a valid JPG image.');
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
}
