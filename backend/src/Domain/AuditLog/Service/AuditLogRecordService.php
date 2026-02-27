<?php

namespace App\Domain\AuditLog\Service;

use App\Domain\AuditLog\Entity\AuditLogEntity;
use App\Domain\AuditLog\Repository\AuditLogRepository;

class AuditLogRecordService
{
    private AuditLogRepository $auditLogRepository;

    public function __construct(AuditLogRepository $auditLogRepository)
    {
        $this->auditLogRepository = $auditLogRepository;
    }

    // ===== AI GENERATED: recordAuditLog =====
    // Purpose: Record an audit log entry for any domain action
    // Inputs: performedByAccountId, actionPerformed, targetEntityType, targetEntityIdentifier, changeDetails
    // Returns: void
    // Flow:
    // 1. Create AuditLogEntity
    // 2. Persist via repository

    public function recordAuditLog(?int $performedByAccountId, string $actionPerformed, string $targetEntityType, ?int $targetEntityIdentifier = null, ?array $changeDetails = null): void
    {
        $entity = new AuditLogEntity();
        $entity->setPerformedByAccountId($performedByAccountId);
        $entity->setActionPerformed($actionPerformed);
        $entity->setTargetEntityType($targetEntityType);
        $entity->setTargetEntityIdentifier($targetEntityIdentifier);
        $entity->setChangeDetails($changeDetails);

        $this->auditLogRepository->persistAuditLog($entity);
    }

    // ===== AI GENERATED: getAllAuditLogs =====
    // Purpose: Retrieve all audit log entries (Admin only)
    // Inputs: none
    // Returns: array of audit log data arrays

    public function getAllAuditLogs(): array
    {
        $entities = $this->auditLogRepository->findAllAuditLogs();
        $results = [];
        foreach ($entities as $entity) {
            $results[] = [
                'auditLogIdentifier' => $entity->getAuditLogIdentifier(),
                'performedByAccountId' => $entity->getPerformedByAccountId(),
                'actionPerformed' => $entity->getActionPerformed(),
                'targetEntityType' => $entity->getTargetEntityType(),
                'targetEntityIdentifier' => $entity->getTargetEntityIdentifier(),
                'changeDetails' => $entity->getChangeDetails(),
                'occurredTimestamp' => $entity->getOccurredTimestamp()->format(\DateTime::ATOM),
            ];
        }
        return $results;
    }
}
