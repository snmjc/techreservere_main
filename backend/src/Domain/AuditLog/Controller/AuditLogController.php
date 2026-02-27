<?php

namespace App\Domain\AuditLog\Controller;

use App\Domain\AuditLog\Service\AuditLogRecordService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/audit-logs')]
class AuditLogController extends AbstractController
{
    use JsonResponseTrait;

    private AuditLogRecordService $auditLogRecordService;

    public function __construct(AuditLogRecordService $auditLogRecordService)
    {
        $this->auditLogRecordService = $auditLogRecordService;
    }

    // ===== AI GENERATED: getAllAuditLogs =====
    // Purpose: Return all audit log entries (Admin only)
    // Inputs: none
    // Returns: JsonResponse

    #[Route('', name: 'audit_log_list', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getAllAuditLogs(): JsonResponse
    {
        $auditLogs = $this->auditLogRecordService->getAllAuditLogs();
        return $this->createSuccessResponse(['auditLogs' => $auditLogs]);
    }
}
