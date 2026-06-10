<?php

namespace App\Domain\Account\Service;

use App\Shared\Utils\RoleDesignationNormalizer;
use App\Shared\Utils\RoleConstants;

class AccountResponseMapperService
{
    public function __construct(private readonly AccountLifecyclePolicyService $accountLifecyclePolicyService)
    {
    }

    public function mapAccountRow(array $row): array
    {
        $roleDesignation = (string)($row['role_designation'] ?? RoleConstants::ROLE_BORROWER);
        $department = strtolower((string)($row['department'] ?? ''));
        $normalizedRole = strtoupper($roleDesignation);
        $isActive = $this->toDatabaseBoolean($row['is_active'] ?? false);
        $isApproved = $this->toDatabaseBoolean($row['is_approved'] ?? false);
        $isVerified = $this->toDatabaseBoolean($row['is_verified'] ?? false);
        $accountStatus = $this->accountLifecyclePolicyService->resolveAccountStatus($isActive, (string)($row['status'] ?? ''), $isApproved);
        $isAdmin = str_contains($normalizedRole, 'ADMIN') || strtolower($roleDesignation) === 'admin';
        $isEmployee = !$isAdmin && (
            str_contains($normalizedRole, 'STAFF')
            || str_contains($normalizedRole, 'EMPLOYEE')
            || str_contains($department, 'staff')
            || str_contains($department, 'employee')
            || str_contains($department, 'technical')
            || str_contains($department, 'maintenance')
            || str_contains($department, 'support')
        );

        $accountType = $isAdmin ? 'Admin' : ($isEmployee ? 'Employee' : 'User');
        $roleLabel = $this->resolveRoleLabelForAccountRow($row, $accountType);
        $idNumber = ($isEmployee && !empty($row['staff_employee_id_number']))
            ? (string)$row['staff_employee_id_number']
            : ($row['id_number'] ?: substr((string)$row['created_timestamp'], 0, 4) . str_pad((string)$row['account_identifier'], 4, '0', STR_PAD_LEFT));
        $firstName = ($isEmployee && !empty($row['staff_first_name'])) ? (string)$row['staff_first_name'] : (string)$row['first_name'];
        $lastName = ($isEmployee && !empty($row['staff_last_name'])) ? (string)$row['staff_last_name'] : (string)$row['last_name'];
        $contactNumber = ($isEmployee && !empty($row['staff_phone_number']))
            ? (string)$row['staff_phone_number']
            : ($row['contact_number'] ? (string)$row['contact_number'] : null);
        $profilePhotoData = ($isEmployee && !empty($row['staff_image_url']))
            ? (string)$row['staff_image_url']
            : (!empty($row['profile_photo_data']) ? (string)$row['profile_photo_data'] : null);

        return [
            'accountIdentifier' => (int)$row['account_identifier'],
            'idNumber' => $idNumber,
            'lastName' => $lastName,
            'firstName' => $firstName,
            'emailAddress' => (string)$row['email_address'],
            'username' => !empty($row['username']) ? (string)$row['username'] : null,
            'roleDesignation' => $this->normalizeRoleDesignation($roleDesignation),
            'roleLabel' => ($isEmployee && !empty($row['staff_role'])) ? (string)$row['staff_role'] : $roleLabel,
            'accountType' => $accountType,
            'accountStatus' => $accountStatus,
            'isActive' => $isActive,
            'isApproved' => $isApproved,
            'isVerified' => $isVerified,
            'verificationStatus' => !empty($row['verification_status']) ? (string)$row['verification_status'] : ($isVerified ? 'verified' : 'unverified'),
            'clerkUserId' => !empty($row['clerk_user_id']) ? (string)$row['clerk_user_id'] : null,
            'actionPermissions' => $this->accountLifecyclePolicyService->buildActionPermissions($accountStatus, $isApproved),
            'contactNumber' => $contactNumber,
            'profilePhotoData' => $profilePhotoData,
            'supportingDocumentName' => !empty($row['signup_supporting_document_name']) ? (string)$row['signup_supporting_document_name'] : null,
            'supportingDocumentMimeType' => !empty($row['signup_supporting_document_mime_type']) ? (string)$row['signup_supporting_document_mime_type'] : null,
            'supportingDocumentPath' => !empty($row['signup_supporting_document_path']) ? (string)$row['signup_supporting_document_path'] : null,
            'supportingDocumentSizeBytes' => isset($row['signup_supporting_document_size_bytes']) ? (int)$row['signup_supporting_document_size_bytes'] : null,
            'supportingDocumentUploadedAt' => !empty($row['signup_supporting_document_uploaded_at']) ? (string)$row['signup_supporting_document_uploaded_at'] : null,
            'supportingDocumentVerificationStatus' => !empty($row['signup_supporting_document_verification_status']) ? (string)$row['signup_supporting_document_verification_status'] : null,
            'supportingDocumentData' => null,
            'createdTimestamp' => (string)$row['created_timestamp'],
            'lastLoginTimestamp' => !empty($row['last_login_timestamp']) ? (string)$row['last_login_timestamp'] : null,
            'inviteSentAt' => !empty($row['invite_sent_at']) ? (string)$row['invite_sent_at'] : null,
            'inviteExpiresAt' => !empty($row['invite_expires_at']) ? (string)$row['invite_expires_at'] : null,
            'inviteAcceptedAt' => !empty($row['invite_accepted_at']) ? (string)$row['invite_accepted_at'] : null,
            'invitedAt' => !empty($row['invited_at']) ? (string)$row['invited_at'] : null,
            'approvedAt' => !empty($row['approved_at']) ? (string)$row['approved_at'] : null,
        ];
    }

    public function mapEmployeeWorkLogRow(array $row): array
    {
        $equipmentList = $this->decodeJsonList($row['requested_equipment_list'] ?? null);
        $reservationDetails = null;
        $assignedStaffName = trim(sprintf(
            '%s %s',
            (string)($row['staff_first_name'] ?? ''),
            (string)($row['staff_last_name'] ?? '')
        ));

        if (!empty($row['reservation_identifier'])) {
            $reservationDetails = [
                'reservationIdentifier' => (int)$row['reservation_identifier'],
                'reservationCode' => (string)($row['reservation_code'] ?? ''),
                'organizationName' => (string)($row['organization_name'] ?? ''),
                'eventDateTime' => $row['event_date_time'] ? (string)$row['event_date_time'] : null,
                'purposeDescription' => $row['purpose_description'] ? (string)$row['purpose_description'] : null,
                'activityType' => $row['activity_type'] ? (string)$row['activity_type'] : null,
                'status' => $row['reservation_status'] ? (string)$row['reservation_status'] : null,
                'requestedEquipmentList' => $equipmentList,
                'requestedQuantity' => isset($row['requested_quantity']) ? (int)$row['requested_quantity'] : null,
                'priorityLevel' => $row['priority_level'] ? (string)$row['priority_level'] : null,
            ];
        }

        return [
            'historyLogId' => isset($row['history_log_id']) ? (int)$row['history_log_id'] : null,
            'staffId' => isset($row['staff_id']) ? (int)$row['staff_id'] : null,
            'reservationId' => isset($row['reservation_id']) ? (int)$row['reservation_id'] : null,
            'taskAssignmentId' => isset($row['task_assignment_id']) ? (int)$row['task_assignment_id'] : null,
            'taskIdentifier' => (int)$row['task_identifier'],
            'taskName' => (string)$row['task_title'],
            'taskDescription' => $row['task_description'] ? (string)$row['task_description'] : null,
            'taskType' => (string)($row['task_type'] ?? ''),
            'status' => (string)($row['task_status'] ?? ''),
            'assignedToAccountId' => $row['assigned_to_account_id'] !== null ? (int)$row['assigned_to_account_id'] : null,
            'taskDateTime' => $row['due_date_timestamp'] ? (string)$row['due_date_timestamp'] : (string)($row['created_timestamp'] ?? ''),
            'dueDateTimestamp' => $row['due_date_timestamp'] ? (string)$row['due_date_timestamp'] : null,
            'createdTimestamp' => (string)($row['created_timestamp'] ?? ''),
            'updatedTimestamp' => (string)($row['updated_timestamp'] ?? ''),
            'reservationDetails' => $reservationDetails,
            'assignments' => [
                'assignedToAccountId' => $row['assigned_to_account_id'] !== null ? (int)$row['assigned_to_account_id'] : null,
                'assignedStaffName' => $assignedStaffName !== '' ? $assignedStaffName : null,
                'assignedStaffIdNumber' => $row['staff_employee_id_number'] ? (string)$row['staff_employee_id_number'] : null,
                'assignedStaffRole' => $row['staff_role'] ? (string)$row['staff_role'] : null,
                'assignmentType' => (string)($row['task_type'] ?? ''),
                'assignedTask' => (string)$row['task_title'],
                'taskAssignmentId' => isset($row['task_assignment_id']) ? (int)$row['task_assignment_id'] : null,
            ],
            'fullTaskInformation' => [
                'taskIdentifier' => (int)$row['task_identifier'],
                'taskAssignmentId' => isset($row['task_assignment_id']) ? (int)$row['task_assignment_id'] : null,
                'description' => $row['task_description'] ? (string)$row['task_description'] : null,
                'type' => (string)($row['task_type'] ?? ''),
                'status' => (string)($row['task_status'] ?? ''),
                'dueDateTimestamp' => $row['due_date_timestamp'] ? (string)$row['due_date_timestamp'] : null,
                'createdTimestamp' => (string)($row['created_timestamp'] ?? ''),
                'updatedTimestamp' => (string)($row['updated_timestamp'] ?? ''),
            ],
        ];
    }

    private function normalizeRoleDesignation(string $roleDesignation): string
    {
        return RoleDesignationNormalizer::normalize($roleDesignation);
    }

    private function resolveRoleLabelForAccountRow(array $row, string $accountType): string
    {
        if ($accountType === 'Admin') {
            return 'Admin';
        }

        $department = trim((string)($row['department'] ?? ''));
        if ($accountType === 'Employee') {
            return $department !== '' ? ucwords($department) : 'Technical Staff';
        }

        if (preg_match('/faculty/i', $department) === 1) {
            return 'Faculty';
        }

        return 'Student';
    }

    private function decodeJsonList(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string)($value ?? '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function toDatabaseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        $normalized = strtolower(trim((string)$value));
        return in_array($normalized, ['1', 't', 'true', 'yes'], true);
    }
}
