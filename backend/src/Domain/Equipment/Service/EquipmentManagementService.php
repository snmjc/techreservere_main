<?php

namespace App\Domain\Equipment\Service;

use App\Domain\Equipment\DTO\EquipmentCreateRequestDTO;
use App\Domain\Equipment\DTO\EquipmentResponseDTO;
use App\Domain\Equipment\Entity\EquipmentEntity;
use App\Domain\Equipment\Repository\EquipmentRepository;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;

class EquipmentManagementService
{
    private const ALLOWED_STATUSES = ['Available', 'Unavailable', 'Under Maintenance', 'Active', 'Inactive', 'Maintenance'];

    public function __construct(private readonly EquipmentRepository $equipmentRepository)
    {
    }

    public function createEquipment(EquipmentCreateRequestDTO $requestDTO): EquipmentResponseDTO
    {
        $payload = $this->validateAndNormalizePayload($requestDTO);

        $equipmentEntity = new EquipmentEntity();
        $this->hydrateEquipmentEntity($equipmentEntity, $payload);
        $this->equipmentRepository->persistEquipment($equipmentEntity);

        return $this->transformEntityToDTO($equipmentEntity);
    }

    public function updateEquipment(int $equipmentIdentifier, EquipmentCreateRequestDTO $requestDTO): EquipmentResponseDTO
    {
        $equipmentEntity = $this->equipmentRepository->find($equipmentIdentifier);
        if ($equipmentEntity === null) {
            throw new DomainNotFoundException('Equipment not found: ' . $equipmentIdentifier);
        }

        $payload = $this->validateAndNormalizePayload($requestDTO);
        $this->hydrateEquipmentEntity($equipmentEntity, $payload);
        $this->equipmentRepository->persistEquipment($equipmentEntity);

        return $this->transformEntityToDTO($equipmentEntity);
    }

    /** @return EquipmentResponseDTO[] */
    public function getAllEquipment(): array
    {
        return array_map(
            fn (EquipmentEntity $entity): EquipmentResponseDTO => $this->transformEntityToDTO($entity),
            $this->equipmentRepository->findAllEquipment()
        );
    }

    /** @return EquipmentResponseDTO[] */
    public function getAvailableEquipment(): array
    {
        return array_map(
            fn (EquipmentEntity $entity): EquipmentResponseDTO => $this->transformEntityToDTO($entity),
            $this->equipmentRepository->findAvailableEquipment()
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
        $equipmentEntity = $this->equipmentRepository->find($equipmentIdentifier);
        if ($equipmentEntity === null) {
            throw new DomainNotFoundException('Equipment not found: ' . $equipmentIdentifier);
        }

        $this->equipmentRepository->removeEquipment($equipmentEntity);
    }

    private function validateAndNormalizePayload(EquipmentCreateRequestDTO $requestDTO): array
    {
        $equipmentName = trim($requestDTO->equipmentName);
        $equipmentCategory = trim($requestDTO->equipmentCategory);
        $equipmentBrand = trim($requestDTO->equipmentBrand);
        $availableQuantity = $requestDTO->availableQuantity;
        $operationalStatus = trim($requestDTO->operationalStatus);
        $description = trim((string) ($requestDTO->description ?? ''));
        $barcode = trim($requestDTO->barcode);
        $serialNumber = trim($requestDTO->serialNumber);
        $photoData = $requestDTO->photoData === null ? null : trim($requestDTO->photoData);

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

        if (!in_array($operationalStatus, self::ALLOWED_STATUSES, true)) {
            throw new DomainValidationException('Invalid operational status.');
        }

        if ($description === '') {
            throw new DomainValidationException('Description is required.');
        }

        if ($barcode === '') {
            throw new DomainValidationException('Barcode is required.');
        }

        if ($serialNumber === '') {
            throw new DomainValidationException('Asset ID is required.');
        }

        if ($photoData !== null && $photoData !== '' && preg_match('/^data:image\/jpeg;base64,[A-Za-z0-9+\/=\r\n]+$/', $photoData) !== 1) {
            throw new DomainValidationException('Equipment photo must be a valid JPG image.');
        }

        $equipmentState = match ($operationalStatus) {
            'Available', 'Active' => 'Available',
            'Under Maintenance', 'Maintenance' => 'Under Maintenance',
            default => 'Unavailable',
        };

        return [
            'equipmentName' => $equipmentName,
            'equipmentCategory' => $equipmentCategory,
            'equipmentBrand' => $equipmentBrand,
            'availableQuantity' => $availableQuantity,
            'operationalStatus' => $operationalStatus,
            'equipmentState' => $equipmentState,
            'description' => $description,
            'barcode' => $barcode,
            'serialNumber' => $serialNumber,
            'photoData' => $photoData === '' ? null : $photoData,
        ];
    }

    private function hydrateEquipmentEntity(EquipmentEntity $equipmentEntity, array $payload): void
    {
        $equipmentEntity->setEquipmentName($payload['equipmentName']);
        $equipmentEntity->setCategoryName($payload['equipmentCategory']);
        $equipmentEntity->setEquipmentBrand($payload['equipmentBrand']);
        $equipmentEntity->setTotalQuantity($payload['availableQuantity']);
        $equipmentEntity->setAvailableQuantity($payload['availableQuantity']);
        $equipmentEntity->setOperationalStatus($payload['operationalStatus']);
        $equipmentEntity->setEquipmentState($payload['equipmentState']);
        $equipmentEntity->setScheduleDescription($payload['description']);
        $equipmentEntity->setBarcode($payload['barcode']);
        $equipmentEntity->setSerialNumber($payload['serialNumber']);
        $equipmentEntity->setPhotoData($payload['photoData']);
    }

    private function transformEntityToDTO(EquipmentEntity $entity): EquipmentResponseDTO
    {
        return new EquipmentResponseDTO(
            equipmentIdentifier: (int) $entity->getEquipmentIdentifier(),
            equipmentName: $entity->getEquipmentName(),
            equipmentCategory: $entity->getCategoryName(),
            equipmentBrand: $entity->getEquipmentBrand(),
            totalQuantity: $entity->getTotalQuantity(),
            availableQuantity: $entity->getAvailableQuantity(),
            operationalStatus: $entity->getOperationalStatus(),
            equipmentState: $entity->getEquipmentState(),
            description: $entity->getScheduleDescription(),
            barcode: $entity->getBarcode(),
            serialNumber: $entity->getSerialNumber(),
            photoData: $entity->getPhotoData(),
            createdTimestamp: $entity->getCreatedTimestamp()->format(\DateTime::ATOM),
            updatedTimestamp: $entity->getUpdatedTimestamp()->format(\DateTime::ATOM)
        );
    }
}
