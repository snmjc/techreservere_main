<?php

namespace App\Domain\Task\Controller;

use App\Domain\Task\Service\TaskManagementService;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/tasks')]
class TaskController extends AbstractController
{
    use JsonResponseTrait;

    private TaskManagementService $taskManagementService;
    private Connection $connection;

    public function __construct(TaskManagementService $taskManagementService, Connection $connection)
    {
        $this->taskManagementService = $taskManagementService;
        $this->connection = $connection;
    }

    #[Route('', name: 'task_list', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function listTasks(): JsonResponse
    {
        return $this->createSuccessResponse(['tasks' => $this->fetchTaskRows()]);
    }

    #[Route('', name: 'task_create', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function createTask(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $basicValidationError = $this->validateTaskPayloadBasics($body);
        if ($basicValidationError !== null) {
            return $basicValidationError;
        }

        $confirmationError = $this->validateEmergencyOverrideIfNeeded($request, $body, 'creating');
        if ($confirmationError !== null) {
            return $confirmationError;
        }

        try {
            $reservationIdentifier = $this->normalizeNullableInt($body['reservationIdentifier'] ?? null);
            $assignedToAccountId = $this->normalizeNullableInt($body['assignedToAccountId'] ?? null);
            $this->validateLinkedRecords($reservationIdentifier, $assignedToAccountId);

            $dto = $this->taskManagementService->createTask(
                taskTitle: $body['taskTitle'] ?? '',
                taskDescription: $body['taskDescription'] ?? null,
                taskType: $body['taskType'] ?? '',
                taskStatus: $body['taskStatus'] ?? 'Pending',
                reservationIdentifier: $reservationIdentifier,
                assignedToAccountId: $assignedToAccountId,
                dueDateTimestamp: $this->normalizeNullableString($body['dueDateTimestamp'] ?? null)
            );
            $this->syncHistoryLog($dto->taskIdentifier, $reservationIdentifier, $assignedToAccountId);

            return $this->createSuccessResponse([
                'task' => $this->fetchTaskById($dto->taskIdentifier),
            ], 201);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('TaskValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/{taskIdentifier}', name: 'task_update', requirements: ['taskIdentifier' => '\d+'], methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateTask(int $taskIdentifier, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $basicValidationError = $this->validateTaskPayloadBasics($body);
        if ($basicValidationError !== null) {
            return $basicValidationError;
        }

        $confirmationError = $this->validateEmergencyOverrideIfNeeded($request, $body, 'updating');
        if ($confirmationError !== null) {
            return $confirmationError;
        }

        try {
            $reservationIdentifier = $this->normalizeNullableInt($body['reservationIdentifier'] ?? null);
            $assignedToAccountId = $this->normalizeNullableInt($body['assignedToAccountId'] ?? null);
            $this->validateLinkedRecords($reservationIdentifier, $assignedToAccountId);

            $dto = $this->taskManagementService->updateTask(
                taskIdentifier: $taskIdentifier,
                taskTitle: $body['taskTitle'] ?? '',
                taskDescription: $body['taskDescription'] ?? null,
                taskType: $body['taskType'] ?? '',
                taskStatus: $body['taskStatus'] ?? 'Pending',
                reservationIdentifier: $reservationIdentifier,
                assignedToAccountId: $assignedToAccountId,
                dueDateTimestamp: $this->normalizeNullableString($body['dueDateTimestamp'] ?? null)
            );
            $this->syncHistoryLog($dto->taskIdentifier, $reservationIdentifier, $assignedToAccountId);

            return $this->createSuccessResponse([
                'task' => $this->fetchTaskById($dto->taskIdentifier),
            ]);
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('TaskNotFound', $exception->getMessage(), 404);
        } catch (DomainValidationException $exception) {
            return $this->createErrorResponse('TaskValidationFailed', $exception->getMessage(), 422);
        }
    }

    #[Route('/{taskIdentifier}', name: 'task_delete', requirements: ['taskIdentifier' => '\d+'], methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteTask(int $taskIdentifier, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $authenticatedAdminId = $this->resolveAuthenticatedAccountIdentifier($request);
        $securityError = $this->validateResponsibleAdminCredentials(
            $authenticatedAdminId,
            (string)($body['confirmedAdminEmail'] ?? ''),
            (string)($body['confirmedAdminPassword'] ?? ''),
            'deleting'
        );
        if ($securityError !== null) {
            return $securityError;
        }

        try {
            $this->taskManagementService->deleteTask($taskIdentifier);
        } catch (DomainNotFoundException $exception) {
            return $this->createErrorResponse('TaskNotFound', $exception->getMessage(), 404);
        }

        return $this->createSuccessResponse([
            'message' => 'Task assignment deleted.',
            'taskIdentifier' => $taskIdentifier,
        ]);
    }

    #[Route('/{taskIdentifier}/status', name: 'task_update_status', methods: ['PUT'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function updateTaskStatus(int $taskIdentifier, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $dto = $this->taskManagementService->updateTaskStatus($taskIdentifier, $body['taskStatus'] ?? '');
        return $this->createSuccessResponse($dto->toResponseArray());
    }

    #[Route('/reservation/{reservationIdentifier}', name: 'task_by_reservation', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getTasksByReservation(int $reservationIdentifier): JsonResponse
    {
        $dtos = $this->taskManagementService->getTasksByReservation($reservationIdentifier);
        $responseList = array_map(fn($dto) => $dto->toResponseArray(), $dtos);
        return $this->createSuccessResponse(['tasks' => $responseList]);
    }

    private function validateEmergencyOverrideIfNeeded(Request $request, array $body, string $actionName): ?JsonResponse
    {
        $missingOverrideFields = [];
        if ($this->normalizeNullableInt($body['reservationIdentifier'] ?? null) === null) {
            $missingOverrideFields[] = 'reservation';
        }
        if ($this->normalizeNullableInt($body['assignedToAccountId'] ?? null) === null) {
            $missingOverrideFields[] = 'assigned staff';
        }
        if ($this->normalizeNullableString($body['dueDateTimestamp'] ?? null) === null) {
            $missingOverrideFields[] = 'due date';
        }

        if ($missingOverrideFields === []) {
            return null;
        }

        if (empty($body['emergencyOverride'])) {
            return $this->createErrorResponse(
                'EmergencyOverrideRequired',
                'Emergency override is required when saving without ' . implode(', ', $missingOverrideFields) . '.',
                422
            );
        }

        return $this->validateResponsibleAdminCredentials(
            $this->resolveAuthenticatedAccountIdentifier($request),
            (string)($body['confirmedAdminEmail'] ?? ''),
            (string)($body['confirmedAdminPassword'] ?? ''),
            $actionName . ' with emergency override'
        );
    }

    private function validateTaskPayloadBasics(array $body): ?JsonResponse
    {
        $taskTitle = trim((string)($body['taskTitle'] ?? ''));
        $taskType = trim((string)($body['taskType'] ?? ''));
        $taskStatus = trim((string)($body['taskStatus'] ?? 'Pending'));

        if ($taskTitle === '') {
            return $this->createErrorResponse('TaskValidationFailed', 'Task name is required.', 422);
        }

        if (mb_strlen($taskTitle) > 200) {
            return $this->createErrorResponse('TaskValidationFailed', 'Task name must not exceed 200 characters.', 422);
        }

        if ($taskType === '') {
            return $this->createErrorResponse('TaskValidationFailed', 'Task type is required.', 422);
        }

        if (!in_array($taskStatus, ['Pending', 'In Progress', 'Completed', 'Cancelled'], true)) {
            return $this->createErrorResponse('TaskValidationFailed', 'Invalid task status: ' . $taskStatus, 422);
        }

        return null;
    }

    private function validateLinkedRecords(?int $reservationIdentifier, ?int $assignedToAccountId): void
    {
        if ($reservationIdentifier !== null) {
            $reservationExists = (bool)$this->connection->fetchOne(
                'SELECT 1 FROM reservations WHERE reservation_identifier = :reservationIdentifier',
                ['reservationIdentifier' => $reservationIdentifier],
                ['reservationIdentifier' => ParameterType::INTEGER]
            );
            if (!$reservationExists) {
                throw new DomainValidationException('Selected reservation was not found.');
            }
        }

        if ($assignedToAccountId !== null) {
            $staffExists = (bool)$this->connection->fetchOne(
                "SELECT 1
                 FROM accounts
                 LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
                 WHERE accounts.account_identifier = :accountIdentifier
                   AND accounts.role_designation = 'ROLE_STAFF'
                   AND COALESCE(accounts.is_active, TRUE) = TRUE",
                ['accountIdentifier' => $assignedToAccountId],
                ['accountIdentifier' => ParameterType::INTEGER]
            );
            if (!$staffExists) {
                throw new DomainValidationException('Selected assigned staff was not found or is inactive.');
            }
        }
    }

    private function syncHistoryLog(int $taskIdentifier, ?int $reservationIdentifier, ?int $assignedToAccountId): void
    {
        $this->connection->executeStatement(
            'DELETE FROM history_logs WHERE task_assignment_id = :taskIdentifier',
            ['taskIdentifier' => $taskIdentifier],
            ['taskIdentifier' => ParameterType::INTEGER]
        );

        if ($reservationIdentifier === null || $assignedToAccountId === null) {
            return;
        }

        $staffId = $this->connection->fetchOne(
            'SELECT id FROM staff_info WHERE account_identifier = :accountIdentifier',
            ['accountIdentifier' => $assignedToAccountId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        if (!$staffId) {
            return;
        }

        $this->connection->executeStatement(
            'INSERT INTO history_logs (staff_id, reservation_id, task_assignment_id)
             VALUES (:staffId, :reservationIdentifier, :taskIdentifier)
             ON CONFLICT (staff_id, reservation_id, task_assignment_id) DO NOTHING',
            [
                'staffId' => (int)$staffId,
                'reservationIdentifier' => $reservationIdentifier,
                'taskIdentifier' => $taskIdentifier,
            ],
            [
                'staffId' => ParameterType::INTEGER,
                'reservationIdentifier' => ParameterType::INTEGER,
                'taskIdentifier' => ParameterType::INTEGER,
            ]
        );
    }

    private function fetchTaskById(int $taskIdentifier): ?array
    {
        $rows = $this->fetchTaskRows($taskIdentifier);
        return $rows[0] ?? null;
    }

    private function fetchTaskRows(?int $taskIdentifier = null): array
    {
        $params = [];
        $types = [];
        $where = '';
        if ($taskIdentifier !== null) {
            $where = 'WHERE tasks.task_identifier = :taskIdentifier';
            $params['taskIdentifier'] = $taskIdentifier;
            $types['taskIdentifier'] = ParameterType::INTEGER;
        }

        $rows = $this->connection->fetchAllAssociative(
            "SELECT tasks.task_identifier,
                    tasks.reservation_identifier,
                    tasks.task_title,
                    tasks.task_description,
                    tasks.task_type,
                    tasks.task_status,
                    tasks.assigned_to_account_id,
                    tasks.due_date_timestamp,
                    tasks.created_timestamp,
                    reservations.reservation_code,
                    reservations.organization_name,
                    reservations.event_date_time,
                    reservations.current_status AS reservation_status,
                    staff_info.employee_id_number AS staff_employee_id_number,
                    COALESCE(staff_info.first_name, accounts.first_name) AS staff_first_name,
                    COALESCE(staff_info.last_name, accounts.last_name) AS staff_last_name,
                    COALESCE(staff_info.role, accounts.department) AS staff_role
             FROM tasks
             LEFT JOIN reservations ON reservations.reservation_identifier = tasks.reservation_identifier
             LEFT JOIN accounts ON accounts.account_identifier = tasks.assigned_to_account_id
             LEFT JOIN staff_info ON staff_info.account_identifier = accounts.account_identifier
             {$where}
             ORDER BY COALESCE(tasks.due_date_timestamp, tasks.created_timestamp) DESC, tasks.task_identifier DESC",
            $params,
            $types
        );

        return array_map(fn (array $row): array => $this->mapTaskRow($row), $rows);
    }

    private function mapTaskRow(array $row): array
    {
        $reservationIdentifier = $row['reservation_identifier'] !== null ? (int)$row['reservation_identifier'] : null;
        $reservationLabel = null;
        if ($reservationIdentifier !== null) {
            $reservationParts = [
                $row['reservation_code'] ? (string)$row['reservation_code'] : '#' . $reservationIdentifier,
                $row['organization_name'] ? (string)$row['organization_name'] : null,
            ];
            $reservationLabel = implode(' - ', array_filter($reservationParts));
        }

        $staffName = trim(sprintf('%s %s', (string)($row['staff_first_name'] ?? ''), (string)($row['staff_last_name'] ?? '')));

        return [
            'taskIdentifier' => (int)$row['task_identifier'],
            'reservationIdentifier' => $reservationIdentifier,
            'reservationLabel' => $reservationLabel,
            'reservationStatus' => $row['reservation_status'] ? (string)$row['reservation_status'] : null,
            'taskTitle' => (string)$row['task_title'],
            'taskDescription' => $row['task_description'] ? (string)$row['task_description'] : null,
            'taskType' => (string)$row['task_type'],
            'taskStatus' => (string)$row['task_status'],
            'assignedToAccountId' => $row['assigned_to_account_id'] !== null ? (int)$row['assigned_to_account_id'] : null,
            'assignedStaffName' => $staffName !== '' ? $staffName : null,
            'assignedStaffIdNumber' => $row['staff_employee_id_number'] ? (string)$row['staff_employee_id_number'] : null,
            'assignedStaffRole' => $row['staff_role'] ? (string)$row['staff_role'] : null,
            'dueDateTimestamp' => $row['due_date_timestamp'] ? (string)$row['due_date_timestamp'] : null,
            'createdTimestamp' => (string)$row['created_timestamp'],
        ];
    }

    private function validateResponsibleAdminCredentials(int $authenticatedAdminId, string $confirmedAdminEmail, string $confirmedAdminPassword, string $actionName): ?JsonResponse
    {
        $normalizedConfirmedAdminEmail = $this->normalizeEmailForConfirmation($confirmedAdminEmail);

        if ($normalizedConfirmedAdminEmail === '' || $authenticatedAdminId <= 0) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type the responsible admin email before %s this task assignment.', $actionName),
                422
            );
        }

        if (trim($confirmedAdminPassword) === '') {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type the responsible admin password before %s this task assignment.', $actionName),
                422
            );
        }

        $confirmedAdmin = $this->connection->fetchAssociative(
            "SELECT email_address, password_hash
             FROM accounts
             WHERE account_identifier = :accountIdentifier
               AND role_designation IN ('ROLE_ADMIN', 'ADMIN')
               AND COALESCE(is_active, TRUE) = TRUE
             LIMIT 1",
            ['accountIdentifier' => $authenticatedAdminId],
            ['accountIdentifier' => ParameterType::INTEGER]
        );

        $responsibleAdminEmail = $this->normalizeEmailForConfirmation((string)($confirmedAdmin['email_address'] ?? ''));
        if (!$confirmedAdmin || $normalizedConfirmedAdminEmail !== $responsibleAdminEmail) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type your exact admin email before %s this task assignment.', $actionName),
                422
            );
        }

        $passwordHash = (string)($confirmedAdmin['password_hash'] ?? '');
        if ($passwordHash === '' || !password_verify($confirmedAdminPassword, $passwordHash)) {
            return $this->createErrorResponse(
                'SecurityConfirmationFailed',
                sprintf('Please type your exact admin password before %s this task assignment.', $actionName),
                422
            );
        }

        return null;
    }

    private function resolveAuthenticatedAccountIdentifier(Request $request): int
    {
        $authenticatedIdentity = $request->attributes->get('authenticatedIdentity', []);
        $accountIdentifier = (int)($authenticatedIdentity['accountIdentifier'] ?? 0);
        if ($accountIdentifier > 0) {
            return $accountIdentifier;
        }

        $authorizationHeader = (string)$request->headers->get('Authorization', '');
        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            return 0;
        }

        $token = trim(substr($authorizationHeader, 7));
        $localPayload = json_decode(base64_decode($token, true) ?: '', true);
        if (is_array($localPayload)) {
            return (int)($localPayload['accountId'] ?? $localPayload['accountIdentifier'] ?? 0);
        }

        return 0;
    }

    private function normalizeEmailForConfirmation(string $emailAddress): string
    {
        return strtolower(trim(preg_replace('/\s+/', '', $emailAddress) ?? ''));
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $integerValue = (int)$value;
        return $integerValue > 0 ? $integerValue : null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $text = trim((string)($value ?? ''));
        return $text === '' ? null : $text;
    }
}
