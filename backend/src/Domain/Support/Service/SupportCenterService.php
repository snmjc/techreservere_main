<?php

namespace App\Domain\Support\Service;

use App\Domain\Account\Repository\AccountRepository;
use App\Domain\Notification\Service\NotificationDispatchService;
use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\RoleConstants;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class SupportCenterService
{
    private const FEEDBACK_STATUSES = ['Submitted', 'In Review', 'Resolved', 'Closed'];
    private const DAMAGE_REPORT_STATUSES = ['Submitted', 'In Review', 'Scheduled', 'Resolved', 'Closed'];
    private const FEEDBACK_CATEGORIES = ['General', 'Bug Report', 'Feature Request', 'Usability', 'Access Issue'];
    private const DAMAGE_RESOURCE_TYPES = ['Venue', 'Equipment', 'Facility', 'Other'];
    private const DAMAGE_ISSUE_TYPES = ['Damage', 'Missing Parts', 'Cleanliness', 'Safety Concern', 'Other'];

    public function __construct(
        private readonly Connection $connection,
        private readonly AccountRepository $accountRepository,
        private readonly NotificationDispatchService $notificationDispatchService
    ) {
    }

    public function createFeedback(int $accountIdentifier, array $payload): array
    {
        $category = $this->normalizeAllowedValue(
            trim((string) ($payload['category'] ?? 'General')),
            self::FEEDBACK_CATEGORIES,
            'Invalid feedback category.'
        );
        $subjectLine = trim((string) ($payload['subjectLine'] ?? ''));
        $messageBody = trim((string) ($payload['messageBody'] ?? ''));

        if ($subjectLine === '' || mb_strlen($subjectLine) < 6) {
            throw new DomainValidationException('Feedback subject must be at least 6 characters.');
        }

        if ($messageBody === '' || mb_strlen($messageBody) < 12) {
            throw new DomainValidationException('Feedback details must be at least 12 characters.');
        }

        $identifier = (int) $this->connection->fetchOne(
            'INSERT INTO feedback_submissions (account_identifier, category, subject_line, message_body, current_status, created_timestamp, updated_timestamp)
             VALUES (:accountIdentifier, :category, :subjectLine, :messageBody, :currentStatus, NOW(), NOW())
             RETURNING feedback_identifier',
            [
                'accountIdentifier' => $accountIdentifier,
                'category' => $category,
                'subjectLine' => $subjectLine,
                'messageBody' => $messageBody,
                'currentStatus' => 'Submitted',
            ],
            [
                'accountIdentifier' => ParameterType::INTEGER,
                'category' => ParameterType::STRING,
                'subjectLine' => ParameterType::STRING,
                'messageBody' => ParameterType::STRING,
                'currentStatus' => ParameterType::STRING,
            ]
        );

        $this->notifyAdmins(
            'New feedback received',
            sprintf('Feedback "%s" was submitted for review.', $subjectLine),
            'System'
        );

        return $this->getFeedbackById($identifier);
    }

    public function listFeedback(int $accountIdentifier, string $resolvedRole): array
    {
        $isAdmin = in_array($resolvedRole, [RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER], true);
        $sql = 'SELECT * FROM feedback_submissions';
        $params = [];
        $types = [];

        if (!$isAdmin) {
            $sql .= ' WHERE account_identifier = :accountIdentifier';
            $params['accountIdentifier'] = $accountIdentifier;
            $types['accountIdentifier'] = ParameterType::INTEGER;
        }

        $sql .= ' ORDER BY created_timestamp DESC, feedback_identifier DESC';

        return array_map([$this, 'mapFeedbackRow'], $this->connection->fetchAllAssociative($sql, $params, $types));
    }

    public function updateFeedbackStatus(int $feedbackIdentifier, array $payload): array
    {
        $status = $this->normalizeAllowedValue(
            trim((string) ($payload['status'] ?? '')),
            self::FEEDBACK_STATUSES,
            'Invalid feedback status.'
        );
        $adminNotes = $this->normalizeOptionalText($payload['adminNotes'] ?? null);

        $affectedRows = $this->connection->executeStatement(
            'UPDATE feedback_submissions
             SET current_status = :status,
                 admin_notes = :adminNotes,
                 updated_timestamp = NOW()
             WHERE feedback_identifier = :feedbackIdentifier',
            [
                'status' => $status,
                'adminNotes' => $adminNotes,
                'feedbackIdentifier' => $feedbackIdentifier,
            ],
            [
                'status' => ParameterType::STRING,
                'adminNotes' => ParameterType::STRING,
                'feedbackIdentifier' => ParameterType::INTEGER,
            ]
        );

        if ($affectedRows < 1) {
            throw new DomainValidationException('Feedback submission was not found.');
        }

        return $this->getFeedbackById($feedbackIdentifier);
    }

    public function createDamageReport(int $accountIdentifier, array $payload): array
    {
        $resourceType = $this->normalizeAllowedValue(
            trim((string) ($payload['resourceType'] ?? 'Other')),
            self::DAMAGE_RESOURCE_TYPES,
            'Invalid resource type.'
        );
        $issueType = $this->normalizeAllowedValue(
            trim((string) ($payload['issueType'] ?? 'Damage')),
            self::DAMAGE_ISSUE_TYPES,
            'Invalid damage issue type.'
        );
        $resourceIdentifier = max((int) ($payload['resourceIdentifier'] ?? 0), 0);
        $resourceName = trim((string) ($payload['resourceName'] ?? ''));
        $descriptionText = trim((string) ($payload['descriptionText'] ?? ''));
        $imageData = $this->normalizeImageData($payload['imageData'] ?? null);

        if ($resourceName === '' || mb_strlen($resourceName) < 2) {
            throw new DomainValidationException('Resource name must be at least 2 characters.');
        }

        if ($descriptionText === '' || mb_strlen($descriptionText) < 12) {
            throw new DomainValidationException('Damage report details must be at least 12 characters.');
        }

        $identifier = (int) $this->connection->fetchOne(
            'INSERT INTO damage_reports (
                account_identifier,
                resource_type,
                resource_identifier,
                resource_name,
                issue_type,
                description_text,
                image_data,
                current_status,
                created_timestamp,
                updated_timestamp
             ) VALUES (
                :accountIdentifier,
                :resourceType,
                :resourceIdentifier,
                :resourceName,
                :issueType,
                :descriptionText,
                :imageData,
                :currentStatus,
                NOW(),
                NOW()
             ) RETURNING damage_report_identifier',
            [
                'accountIdentifier' => $accountIdentifier,
                'resourceType' => $resourceType,
                'resourceIdentifier' => $resourceIdentifier > 0 ? $resourceIdentifier : null,
                'resourceName' => $resourceName,
                'issueType' => $issueType,
                'descriptionText' => $descriptionText,
                'imageData' => $imageData,
                'currentStatus' => 'Submitted',
            ],
            [
                'accountIdentifier' => ParameterType::INTEGER,
                'resourceType' => ParameterType::STRING,
                'resourceIdentifier' => ParameterType::INTEGER,
                'resourceName' => ParameterType::STRING,
                'issueType' => ParameterType::STRING,
                'descriptionText' => ParameterType::STRING,
                'imageData' => ParameterType::STRING,
                'currentStatus' => ParameterType::STRING,
            ]
        );

        $this->notifyAdmins(
            'New damage report submitted',
            sprintf('%s report filed for %s.', $issueType, $resourceName),
            'Maintenance'
        );

        return $this->getDamageReportById($identifier);
    }

    public function listDamageReports(int $accountIdentifier, string $resolvedRole): array
    {
        $isAdmin = in_array($resolvedRole, [RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER], true);
        $sql = 'SELECT * FROM damage_reports';
        $params = [];
        $types = [];

        if (!$isAdmin) {
            $sql .= ' WHERE account_identifier = :accountIdentifier';
            $params['accountIdentifier'] = $accountIdentifier;
            $types['accountIdentifier'] = ParameterType::INTEGER;
        }

        $sql .= ' ORDER BY created_timestamp DESC, damage_report_identifier DESC';

        return array_map([$this, 'mapDamageReportRow'], $this->connection->fetchAllAssociative($sql, $params, $types));
    }

    public function updateDamageReportStatus(int $damageReportIdentifier, array $payload): array
    {
        $status = $this->normalizeAllowedValue(
            trim((string) ($payload['status'] ?? '')),
            self::DAMAGE_REPORT_STATUSES,
            'Invalid damage report status.'
        );
        $adminNotes = $this->normalizeOptionalText($payload['adminNotes'] ?? null);

        $affectedRows = $this->connection->executeStatement(
            'UPDATE damage_reports
             SET current_status = :status,
                 admin_notes = :adminNotes,
                 updated_timestamp = NOW()
             WHERE damage_report_identifier = :damageReportIdentifier',
            [
                'status' => $status,
                'adminNotes' => $adminNotes,
                'damageReportIdentifier' => $damageReportIdentifier,
            ],
            [
                'status' => ParameterType::STRING,
                'adminNotes' => ParameterType::STRING,
                'damageReportIdentifier' => ParameterType::INTEGER,
            ]
        );

        if ($affectedRows < 1) {
            throw new DomainValidationException('Damage report was not found.');
        }

        return $this->getDamageReportById($damageReportIdentifier);
    }

    private function getFeedbackById(int $feedbackIdentifier): array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM feedback_submissions WHERE feedback_identifier = :feedbackIdentifier LIMIT 1',
            ['feedbackIdentifier' => $feedbackIdentifier],
            ['feedbackIdentifier' => ParameterType::INTEGER]
        );

        if (!is_array($row)) {
            throw new DomainValidationException('Feedback submission was not found.');
        }

        return $this->mapFeedbackRow($row);
    }

    private function getDamageReportById(int $damageReportIdentifier): array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM damage_reports WHERE damage_report_identifier = :damageReportIdentifier LIMIT 1',
            ['damageReportIdentifier' => $damageReportIdentifier],
            ['damageReportIdentifier' => ParameterType::INTEGER]
        );

        if (!is_array($row)) {
            throw new DomainValidationException('Damage report was not found.');
        }

        return $this->mapDamageReportRow($row);
    }

    private function mapFeedbackRow(array $row): array
    {
        return [
            'feedbackIdentifier' => (int) ($row['feedback_identifier'] ?? 0),
            'accountIdentifier' => (int) ($row['account_identifier'] ?? 0),
            'category' => (string) ($row['category'] ?? 'General'),
            'subjectLine' => (string) ($row['subject_line'] ?? ''),
            'messageBody' => (string) ($row['message_body'] ?? ''),
            'currentStatus' => (string) ($row['current_status'] ?? 'Submitted'),
            'adminNotes' => $row['admin_notes'] ?? null,
            'createdTimestamp' => $this->formatTimestamp($row['created_timestamp'] ?? null),
            'updatedTimestamp' => $this->formatTimestamp($row['updated_timestamp'] ?? null),
        ];
    }

    private function mapDamageReportRow(array $row): array
    {
        return [
            'damageReportIdentifier' => (int) ($row['damage_report_identifier'] ?? 0),
            'accountIdentifier' => (int) ($row['account_identifier'] ?? 0),
            'resourceType' => (string) ($row['resource_type'] ?? 'Other'),
            'resourceIdentifier' => ($row['resource_identifier'] ?? null) !== null ? (int) $row['resource_identifier'] : null,
            'resourceName' => (string) ($row['resource_name'] ?? ''),
            'issueType' => (string) ($row['issue_type'] ?? 'Damage'),
            'descriptionText' => (string) ($row['description_text'] ?? ''),
            'imageData' => $row['image_data'] ?? null,
            'currentStatus' => (string) ($row['current_status'] ?? 'Submitted'),
            'adminNotes' => $row['admin_notes'] ?? null,
            'createdTimestamp' => $this->formatTimestamp($row['created_timestamp'] ?? null),
            'updatedTimestamp' => $this->formatTimestamp($row['updated_timestamp'] ?? null),
        ];
    }

    private function notifyAdmins(string $title, string $message, string $type): void
    {
        $adminAccounts = $this->accountRepository->findActiveApprovedAccountsByRoles([RoleConstants::ROLE_ADMIN]);
        foreach ($adminAccounts as $adminAccount) {
            $accountIdentifier = (int) ($adminAccount->getAccountIdentifier() ?? 0);
            if ($accountIdentifier > 0) {
                $this->notificationDispatchService->sendNotification($accountIdentifier, $title, $message, $type);
            }
        }
    }

    private function normalizeAllowedValue(string $value, array $allowedValues, string $errorMessage): string
    {
        if ($value === '') {
            return $allowedValues[0];
        }

        foreach ($allowedValues as $allowedValue) {
            if (strcasecmp($allowedValue, $value) === 0) {
                return $allowedValue;
            }
        }

        throw new DomainValidationException($errorMessage);
    }

    private function normalizeOptionalText(mixed $value): ?string
    {
        $normalizedValue = trim((string) $value);
        return $normalizedValue === '' ? null : $normalizedValue;
    }

    private function normalizeImageData(mixed $value): ?string
    {
        $normalizedValue = trim((string) $value);
        if ($normalizedValue === '') {
            return null;
        }

        if (preg_match('/^data:image\/(?:jpeg|jpg|png|webp);base64,[A-Za-z0-9+\/=\r\n]+$/i', $normalizedValue) !== 1) {
            throw new DomainValidationException('Damage report image must be a valid JPG, PNG, or WebP image.');
        }

        return $normalizedValue;
    }

    private function formatTimestamp(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        $timestamp = trim((string) $value);
        if ($timestamp === '') {
            return (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        }

        return (new \DateTimeImmutable($timestamp))->format(\DateTimeInterface::ATOM);
    }
}
