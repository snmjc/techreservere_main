<?php

namespace App\Domain\Task\Service;

use App\Domain\Task\DTO\TaskLinkedRecordsDTO;
use App\Domain\Task\DTO\TaskMutationRequestDTO;
use App\Domain\Task\DTO\TaskSecurityConfirmationDTO;
use Symfony\Component\HttpFoundation\Request;

class TaskMutationPayloadService
{
    private const ALLOWED_STATUSES = ['Pending', 'In Progress', 'Completed', 'Cancelled'];

    public function __construct(
        private readonly TaskLinkedRecordValidator $taskLinkedRecordValidator,
        private readonly TaskRequestIdentityResolver $taskRequestIdentityResolver,
        private readonly TaskSecurityConfirmationService $taskSecurityConfirmationService
    ) {
    }

    public function validateMutationPreflight(Request $request, array $body, string $actionName): ?array
    {
        $basicValidationError = $this->validateTaskPayloadBasics($body);
        if ($basicValidationError !== null) {
            return $basicValidationError;
        }

        return $this->validateEmergencyOverrideIfNeeded($request, $body, $actionName);
    }

    public function resolveLinkedRecords(array $body): TaskLinkedRecordsDTO
    {
        $linkedRecords = new TaskLinkedRecordsDTO(
            $this->normalizeNullableInt($body['reservationIdentifier'] ?? null),
            $this->normalizeNullableInt($body['assignedToAccountId'] ?? null)
        );

        $this->taskLinkedRecordValidator->validateLinkedRecords(
            $linkedRecords->reservationIdentifier,
            $linkedRecords->assignedToAccountId
        );

        return $linkedRecords;
    }

    public function buildTaskMutationRequest(array $body, TaskLinkedRecordsDTO $linkedRecords): TaskMutationRequestDTO
    {
        return new TaskMutationRequestDTO(
            taskTitle: $body['taskTitle'] ?? '',
            taskDescription: $body['taskDescription'] ?? null,
            taskType: $body['taskType'] ?? '',
            taskStatus: $body['taskStatus'] ?? 'Pending',
            reservationIdentifier: $linkedRecords->reservationIdentifier,
            assignedToAccountId: $linkedRecords->assignedToAccountId,
            dueDateTimestamp: $this->normalizeNullableString($body['dueDateTimestamp'] ?? null),
            preparationStartTimestamp: $this->normalizeNullableString($body['preparationStartTimestamp'] ?? null),
            preparationEndTimestamp: $this->normalizeNullableString($body['preparationEndTimestamp'] ?? null)
        );
    }

    public function validateDeleteSecurity(Request $request, array $body): ?array
    {
        return $this->validateResponsibleAdminCredentials($request, $body, 'deleting');
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

        return $this->validateResponsibleAdminCredentials($request, $body, $actionName . ' with emergency override');
    }

    private function validateResponsibleAdminCredentials(Request $request, array $body, string $actionName): ?array
    {
        return $this->taskSecurityConfirmationService->validateResponsibleAdminCredentials(
            new TaskSecurityConfirmationDTO(
                $this->taskRequestIdentityResolver->resolveAuthenticatedAccountIdentifier($request),
                (string)($body['confirmedAdminEmail'] ?? ''),
                (string)($body['confirmedAdminPassword'] ?? ''),
                $actionName
            )
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

        if (!in_array($taskStatus, self::ALLOWED_STATUSES, true)) {
            return $this->error('TaskValidationFailed', 'Invalid task status: ' . $taskStatus, 422);
        }

        return null;
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
