<?php

namespace App\Domain\ReleaseReturn\Service;

use App\Domain\ReleaseReturn\DTO\ReleaseReturnResponseDTO;
use App\Domain\ReleaseReturn\Entity\ReleaseReturnEntity;
use App\Domain\ReleaseReturn\Repository\ReleaseReturnRepository;
use App\Shared\Exceptions\DomainValidationException;

class ReleaseReturnProcessService
{
    private ReleaseReturnRepository $releaseReturnRepository;

    public function __construct(ReleaseReturnRepository $releaseReturnRepository)
    {
        $this->releaseReturnRepository = $releaseReturnRepository;
    }

    // ===== AI GENERATED: processReleaseReturn =====
    // Purpose: Record a release or return transaction
    // Inputs: transaction data fields
    // Returns: ReleaseReturnResponseDTO

    public function processReleaseReturn(int $reservationIdentifier, string $transactionType, array $equipmentItemList, int $quantityProcessed, string $conditionStatus, ?string $remarksDescription, ?int $processedByAccountId): ReleaseReturnResponseDTO
    {
        $allowedTypes = ['Release', 'Return'];
        if (!in_array($transactionType, $allowedTypes, true)) {
            throw new DomainValidationException('Invalid transaction type: ' . $transactionType);
        }

        $entity = new ReleaseReturnEntity();
        $entity->setReservationIdentifier($reservationIdentifier);
        $entity->setTransactionType($transactionType);
        $entity->setEquipmentItemList($equipmentItemList);
        $entity->setQuantityProcessed($quantityProcessed);
        $entity->setConditionStatus($conditionStatus);
        $entity->setRemarksDescription($remarksDescription);
        $entity->setProcessedByAccountId($processedByAccountId);

        $this->releaseReturnRepository->persistReleaseReturn($entity);

        return $this->transformEntityToDTO($entity);
    }

    /** @return ReleaseReturnResponseDTO[] */
    public function getByReservation(int $reservationIdentifier): array
    {
        $entities = $this->releaseReturnRepository->findByReservationIdentifier($reservationIdentifier);
        return array_map(fn($e) => $this->transformEntityToDTO($e), $entities);
    }

    private function transformEntityToDTO(ReleaseReturnEntity $entity): ReleaseReturnResponseDTO
    {
        return new ReleaseReturnResponseDTO(
            releaseReturnIdentifier: $entity->getReleaseReturnIdentifier(),
            reservationIdentifier: $entity->getReservationIdentifier(),
            transactionType: $entity->getTransactionType(),
            equipmentItemList: $entity->getEquipmentItemList(),
            quantityProcessed: $entity->getQuantityProcessed(),
            conditionStatus: $entity->getConditionStatus(),
            remarksDescription: $entity->getRemarksDescription(),
            processedByAccountId: $entity->getProcessedByAccountId(),
            processedTimestamp: $entity->getProcessedTimestamp()->format(\DateTime::ATOM)
        );
    }
}
