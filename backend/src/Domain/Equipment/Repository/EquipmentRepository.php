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

    /** @return EquipmentEntity[] */
    public function findAllEquipment(): array
    {
        return $this->createQueryBuilder('equip')
            ->orderBy('equip.equipmentName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return EquipmentEntity[] */
    public function findAvailableEquipment(): array
    {
        return $this->createQueryBuilder('equip')
            ->where('equip.availableQuantity > 0')
            ->andWhere('equip.equipmentState = :equipmentState OR equip.operationalStatus = :legacyStatus')
            ->setParameter('equipmentState', 'Available')
            ->setParameter('legacyStatus', 'Active')
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

    public function persistEquipment(EquipmentEntity $equipmentEntity): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($equipmentEntity);
        $entityManager->flush();
    }

    public function removeEquipment(EquipmentEntity $equipmentEntity): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($equipmentEntity);
        $entityManager->flush();
    }
}
