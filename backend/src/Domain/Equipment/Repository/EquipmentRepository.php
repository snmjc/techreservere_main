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
        return $this->createQueryBuilder('equip')
            ->orderBy('equip.equipmentName', 'ASC')
            ->getQuery()
            ->getResult();
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
            ->andWhere('equip.equipmentState = :equipmentState')
            ->andWhere('equip.availableQuantity > 0')
            ->setParameter('equipmentState', 'Available')
            ->orderBy('equip.equipmentName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByBarcode(string $barcode): ?EquipmentEntity
    {
        return $this->createQueryBuilder('equip')
            ->where('LOWER(equip.barcode) = LOWER(:barcode)')
            ->setParameter('barcode', trim($barcode))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByAssetId(string $assetId): ?EquipmentEntity
    {
        return $this->createQueryBuilder('equip')
            ->where('LOWER(equip.assetId) = LOWER(:assetId)')
            ->setParameter('assetId', trim($assetId))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
