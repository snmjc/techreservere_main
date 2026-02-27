<?php

namespace App\Domain\Equipment\Repository;

use App\Domain\Equipment\Entity\EquipmentEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipmentEntity::class);
    }

    // ===== AI GENERATED: findAllEquipment =====
    // Purpose: Retrieve all equipment records
    // Inputs: none
    // Returns: EquipmentEntity[]
    // Flow:
    // 1. Query all records from equipment table
    // 2. Return array of entities

    /** @return EquipmentEntity[] */
    public function findAllEquipment(): array
    {
        return $this->findAll();
    }

    // ===== AI GENERATED: findAvailableEquipment =====
    // Purpose: Retrieve equipment with Active status and available quantity > 0
    // Inputs: none
    // Returns: EquipmentEntity[]
    // Flow:
    // 1. Query equipment where operational_status = Active and available_quantity > 0
    // 2. Return filtered array

    /** @return EquipmentEntity[] */
    public function findAvailableEquipment(): array
    {
        return $this->createQueryBuilder('equip')
            ->where('equip.operationalStatus = :activeStatus')
            ->andWhere('equip.availableQuantity > 0')
            ->setParameter('activeStatus', 'Active')
            ->getQuery()
            ->getResult();
    }

    // ===== AI GENERATED: findByCategoryName =====
    // Purpose: Retrieve equipment filtered by category
    // Inputs: categoryName (string)
    // Returns: EquipmentEntity[]
    // Flow:
    // 1. Query equipment by category_name
    // 2. Return filtered array

    /** @return EquipmentEntity[] */
    public function findByCategoryName(string $categoryName): array
    {
        return $this->findBy(['categoryName' => $categoryName]);
    }

    // ===== AI GENERATED: persistEquipment =====
    // Purpose: Persist a new or updated equipment entity
    // Inputs: equipmentEntity (EquipmentEntity)
    // Returns: void
    // Flow:
    // 1. Persist entity via EntityManager
    // 2. Flush changes

    public function persistEquipment(EquipmentEntity $equipmentEntity): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($equipmentEntity);
        $entityManager->flush();
    }

    // ===== AI GENERATED: removeEquipment =====
    // Purpose: Remove an equipment entity from the database
    // Inputs: equipmentEntity (EquipmentEntity)
    // Returns: void
    // Flow:
    // 1. Remove entity via EntityManager
    // 2. Flush changes

    public function removeEquipment(EquipmentEntity $equipmentEntity): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($equipmentEntity);
        $entityManager->flush();
    }
}
