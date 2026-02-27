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
    private EquipmentRepository $equipmentRepository;

    public function __construct(EquipmentRepository $equipmentRepository)
    {
        $this->equipmentRepository = $equipmentRepository;
    }

    // ===== AI GENERATED: createEquipment =====
    // Purpose: Create a new equipment record
    // Inputs: EquipmentCreateRequestDTO
    // Returns: EquipmentResponseDTO
    // Flow:
    // 1. Validate DTO fields
    // 2. Create entity and persist
    // 3. Return response DTO

    public function createEquipment(EquipmentCreateRequestDTO $requestDTO): EquipmentResponseDTO
    {
        if (empty($requestDTO->equipmentName)) {
            throw new DomainValidationException('Equipment name is required.');
        }
        if ($requestDTO->totalQuantity < 0) {
            throw new DomainValidationException('Total quantity cannot be negative.');
        }

        $equipmentEntity = new EquipmentEntity();
        $equipmentEntity->setEquipmentName($requestDTO->equipmentName);
        $equipmentEntity->setCategoryName($requestDTO->categoryName);
        $equipmentEntity->setTotalQuantity($requestDTO->totalQuantity);
        $equipmentEntity->setAvailableQuantity($requestDTO->totalQuantity);
        $equipmentEntity->setOperationalStatus($requestDTO->operationalStatus);
        $equipmentEntity->setScheduleDescription($requestDTO->scheduleDescription);

        $this->equipmentRepository->persistEquipment($equipmentEntity);

        return $this->transformEntityToDTO($equipmentEntity);
    }

    // ===== AI GENERATED: getAllEquipment =====
    // Purpose: Retrieve all equipment records
    // Inputs: none
    // Returns: EquipmentResponseDTO[]
    // Flow:
    // 1. Query repository for all equipment
    // 2. Transform to DTOs

    /** @return EquipmentResponseDTO[] */
    public function getAllEquipment(): array
    {
        $entities = $this->equipmentRepository->findAllEquipment();
        $responseDTOs = [];
        foreach ($entities as $entity) { // entity → DTO loop
            $responseDTOs[] = $this->transformEntityToDTO($entity);
        }
        return $responseDTOs;
    }

    // ===== AI GENERATED: getAvailableEquipment =====
    // Purpose: Retrieve only available equipment (Borrower view)
    // Inputs: none
    // Returns: EquipmentResponseDTO[]
    // Flow:
    // 1. Query repository for available equipment
    // 2. Transform to DTOs

    /** @return EquipmentResponseDTO[] */
    public function getAvailableEquipment(): array
    {
        $entities = $this->equipmentRepository->findAvailableEquipment();
        $responseDTOs = [];
        foreach ($entities as $entity) { // entity → DTO loop
            $responseDTOs[] = $this->transformEntityToDTO($entity);
        }
        return $responseDTOs;
    }

    // ===== AI GENERATED: getEquipmentById =====
    // Purpose: Retrieve single equipment by ID
    // Inputs: equipmentIdentifier (int)
    // Returns: EquipmentResponseDTO
    // Flow:
    // 1. Query repository by ID
    // 2. Throw if not found
    // 3. Transform to DTO

    public function getEquipmentById(int $equipmentIdentifier): EquipmentResponseDTO
    {
        $entity = $this->equipmentRepository->find($equipmentIdentifier);
        if ($entity === null) {
            throw new DomainNotFoundException('Equipment not found: ' . $equipmentIdentifier);
        }
        return $this->transformEntityToDTO($entity);
    }

    // ===== AI GENERATED: transformEntityToDTO =====
    // Purpose: Map EquipmentEntity to EquipmentResponseDTO
    // Inputs: EquipmentEntity
    // Returns: EquipmentResponseDTO
    // Flow:
    // 1. Extract all properties
    // 2. Return DTO

    private function transformEntityToDTO(EquipmentEntity $entity): EquipmentResponseDTO
    {
        return new EquipmentResponseDTO(
            equipmentIdentifier: $entity->getEquipmentIdentifier(),
            equipmentName: $entity->getEquipmentName(),
            categoryName: $entity->getCategoryName(),
            totalQuantity: $entity->getTotalQuantity(),
            availableQuantity: $entity->getAvailableQuantity(),
            operationalStatus: $entity->getOperationalStatus(),
            equipmentState: $entity->getEquipmentState(),
            scheduleDescription: $entity->getScheduleDescription(),
            createdTimestamp: $entity->getCreatedTimestamp()->format(\DateTime::ATOM)
        );
    }
}
