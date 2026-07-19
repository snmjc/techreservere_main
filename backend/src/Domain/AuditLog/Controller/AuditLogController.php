<?php

namespace App\Domain\AuditLog\Controller;

use App\Domain\AuditLog\Service\AuditLogRecordService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

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
    #[RequiresRoles([RoleConstants::ROLE_ADMIN])]
    public function getAllAuditLogs(Request $request): JsonResponse
    {
        $auditLogs = $this->auditLogRecordService->getAllAuditLogs([
            'search' => (string) $request->query->get('search', ''),
            'role' => (string) $request->query->get('role', ''),
            'action' => (string) $request->query->get('action', ''),
            'module' => (string) $request->query->get('module', ''),
            'scope' => (string) $request->query->get('scope', 'both'),
        ]);
        return $this->createSuccessResponse(['auditLogs' => $auditLogs]);
    }
}
