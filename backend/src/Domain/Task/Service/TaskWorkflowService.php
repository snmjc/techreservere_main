<?php

namespace App\Domain\Task\Service;

use App\Domain\Task\DTO\TaskMutationRequestDTO;
use App\Shared\Exceptions\DomainNotFoundException;
use App\Shared\Exceptions\DomainValidationException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\Request;

class TaskWorkflowService
{
    public function __construct(
        private readonly TaskManagementService $taskManagementService,
        private readonly TaskReadService $taskReadService,
        private readonly TaskHistoryLogService $taskHistoryLogService,
        private readonly TaskLinkedRecordValidator $taskLinkedRecordValidator,
        private readonly Connection $connection
    ) {
    }

    public function listTasks(): array
    {
        return $this->success(['tasks' => $this->taskReadService->fetchTaskRows()]);
    }

    public function createTask(Request $request, array $body): array
    {
        $preflightError = $this->validateMutationPreflight($request, $body, 'creating');
        if ($preflightError !== null) {
            return $preflightError;
        }

        try {
            $linkedRecords = $this->resolveLinkedRecords($body);
            $dto = $this->taskManagementService->createTask($this->buildTaskMutationRequest($body, $linkedRecords));
            $this->taskHistoryLogService->syncHistoryLog($dto->taskIdentifier, $linkedRecords['reservationIdentifier'], $linkedRecords['assignedToAccountId']);

            return $this->success([
                'task' => $this->taskReadService->fetchTaskById($dto->taskIdentifier),
            ], 201);
        } catch (DomainValidationException $exception) {
            return $this->error('TaskValidationFailed', $exception->getMessage(), 422);
        }
    }

    public function updateTask(int $taskIdentifier, Request $request, array $body): array
    {
        $preflightError = $this->validateMutationPreflight($request, $body, 'updating');
        if ($preflightError !== null) {
            return $preflightError;
        }

        try {
            $linkedRecords = $this->resolveLinkedRecords($body);
            $dto = $this->taskManagementService->updateTask($taskIdentifier, $this->buildTaskMutationRequest($body, $linkedRecords));
            $this->taskHistoryLogService->syncHistoryLog($dto->taskIdentifier, $linkedRecords['reservationIdentifier'], $linkedRecords['assignedToAccountId']);

            return $this->success([
                'task' => $this->taskReadService->fetchTaskById($dto->taskIdentifier),
            ]);
        } catch (DomainNotFoundException $exception) {
            return $this->error('TaskNotFound', $exception->getMessage(), 404);
        } catch (DomainValidationException $exception) {
            return $this->error('TaskValidationFailed', $exception->getMessage(), 422);
        }
    }

    public function deleteTask(int $taskIdentifier, Request $request, array $body): array
    {
        $securityError = $this->validateResponsibleAdminCredentials(
            $this->resolveAuthenticatedAccountIdentifier($request),
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
            return $this->error('TaskNotFound', $exception->getMessage(), 404);
        }

        return $this->success([
            'message' => 'Task assignment deleted.',
            'taskIdentifier' => $taskIdentifier,
        ]);
    }

    public function updateTaskStatus(int $taskIdentifier, array $body): array
    {
        $dto = $this->taskManagementService->updateTaskStatus($taskIdentifier, $body['taskStatus'] ?? '');

        return $this->success($dto->toResponseArray());
    }

    public function getTasksByReservation(int $reservationIdentifier): array
    {
        $dtos = $this->taskManagementService->getTasksByReservation($reservationIdentifier);

        return $this->success([
            'tasks' => array_map(fn($dto) => $dto->toResponseArray(), $dtos),
        ]);
    }

    private function validateMutationPreflight(Request $request, array $body, string $actionName): ?array
    {
        $basicValidationError = $this->validateTaskPayloadBasics($body);
        if ($basicValidationError !== null) {
            return $basicValidationError;
        }

        return $this->validateEmergencyOverrideIfNeeded($request, $body, $actionName);
    }

    private function validateEmergencyOverrideIfNeeded(Request $request, array $body, string $actionName): ?array
    {
        $missingOverrideFields = $this->resolveMissingOverrideFields($body);
        if ($missingOverrideFields === []) {
            return null;
        }

        if (empty($body['emergencyOverride'])) {
            return $this->error(
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

    private function resolveMissingOverrideFields(array $body): array
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

        return $missingOverrideFields;
    }

    private function resolveLinkedRecords(array $body): array
    {
        $reservationIdentifier = $this->normalizeNullableInt($body['reservationIdentifier'] ?? null);
        $assignedToAccountId = $this->normalizeNullableInt($body['assignedToAccountId'] ?? null);
        $this->taskLinkedRecordValidator->validateLinkedRecords($reservationIdentifier, $assignedToAccountId);

        return [
            'reservationIdentifier' => $reservationIdentifier,
            'assignedToAccountId' => $assignedToAccountId,
        ];
    }

    private function buildTaskMutationRequest(array $body, array $linkedRecords): TaskMutationRequestDTO
    {
        return new TaskMutationRequestDTO(
            taskTitle: $body['taskTitle'] ?? '',
            taskDescription: $body['taskDescription'] ?? null,
            taskType: $body['taskType'] ?? '',
            taskStatus: $body['taskStatus'] ?? 'Pending',
            reservationIdentifier: $linkedRecords['reservationIdentifier'],
            assignedToAccountId: $linkedRecords['assignedToAccountId'],
            dueDateTimestamp: $this->normalizeNullableString($body['dueDateTimestamp'] ?? null)
        );
    }

    private function validateTaskPayloadBasics(array $body): ?array
    {
        $taskTitle = trim((string)($body['taskTitle'] ?? ''));
        $taskType = trim((string)($body['taskType'] ?? ''));
        $taskStatus = trim((string)($body['taskStatus'] ?? 'Pending'));

        if ($taskTitle === '') {
            return $this->error('TaskValidationFailed', 'Task name is required.', 422);
        }

        if (mb_strlen($taskTitle) > 200) {
            return $this->error('TaskValidationFailed', 'Task name must not exceed 200 characters.', 422);
        }

        if ($taskType === '') {
            return $this->error('TaskValidationFailed', 'Task type is required.', 422);
        }

        if (!in_array($taskStatus, ['Pending', 'In Progress', 'Completed', 'Cancelled'], true)) {
            return $this->error('TaskValidationFailed', 'Invalid task status: ' . $taskStatus, 422);
        }

        return null;
    }

    private function validateResponsibleAdminCredentials(int $authenticatedAdminId, string $confirmedAdminEmail, string $confirmedAdminPassword, string $actionName): ?array
    {
        $normalizedConfirmedAdminEmail = $this->normalizeEmailForConfirmation($confirmedAdminEmail);

        if ($normalizedConfirmedAdminEmail === '' || $authenticatedAdminId <= 0) {
            return $this->error(
                'SecurityConfirmationFailed',
                sprintf('Please type the responsible admin email before %s this task assignment.', $actionName),
                422
            );
        }

        if (trim($confirmedAdminPassword) === '') {
            return $this->error(
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
            return $this->error(
                'SecurityConfirmationFailed',
                sprintf('Please type your exact admin email before %s this task assignment.', $actionName),
                422
            );
        }

        $passwordHash = (string)($confirmedAdmin['password_hash'] ?? '');
        if ($passwordHash === '' || !password_verify($confirmedAdminPassword, $passwordHash)) {
            return $this->error(
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

    private function success(array $data, int $status = 200): array
    {
        return ['success' => true, 'status' => $status, 'data' => $data];
    }

    private function error(string $errorCode, string $message, int $status): array
    {
        return [
            'success' => false,
            'errorCode' => $errorCode,
            'message' => $message,
            'status' => $status,
        ];
    }
}
