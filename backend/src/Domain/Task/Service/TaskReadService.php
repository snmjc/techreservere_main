<?php

namespace App\Domain\Task\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class TaskReadService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function fetchTaskById(int $taskIdentifier): ?array
    {
        $rows = $this->fetchTaskRows($taskIdentifier);
        return $rows[0] ?? null;
    }

    public function fetchTaskRows(?int $taskIdentifier = null): array
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
                    reservations.end_date_time,
                    reservations.event_date_time,
                    reservations.venue_identifier,
                    reservations.current_status AS reservation_status,
                    venues.venue_name,
                    staff_info.employee_id_number AS staff_employee_id_number,
                    COALESCE(staff_info.first_name, accounts.first_name) AS staff_first_name,
                    COALESCE(staff_info.last_name, accounts.last_name) AS staff_last_name,
                    COALESCE(staff_info.role, accounts.department) AS staff_role,
                    COALESCE(staff_info.phone_number, accounts.contact_number) AS staff_phone_number
             FROM tasks
             LEFT JOIN reservations ON reservations.reservation_identifier = tasks.reservation_identifier
             LEFT JOIN venues ON venues.venue_identifier = reservations.venue_identifier
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
            'reservationCode' => $row['reservation_code'] ? (string)$row['reservation_code'] : null,
            'facilityName' => $row['venue_name'] ? (string)$row['venue_name'] : null,
            'organizationName' => $row['organization_name'] ? (string)$row['organization_name'] : null,
            'taskTitle' => (string)$row['task_title'],
            'taskDescription' => $row['task_description'] ? (string)$row['task_description'] : null,
            'taskType' => (string)$row['task_type'],
            'taskStatus' => (string)$row['task_status'],
            'assignedToAccountId' => $row['assigned_to_account_id'] !== null ? (int)$row['assigned_to_account_id'] : null,
            'assignedStaffName' => $staffName !== '' ? $staffName : null,
            'assignedStaffIdNumber' => $row['staff_employee_id_number'] ? (string)$row['staff_employee_id_number'] : null,
            'assignedStaffRole' => $row['staff_role'] ? (string)$row['staff_role'] : null,
            'assignedStaffPhone' => $row['staff_phone_number'] ? (string)$row['staff_phone_number'] : null,
            'eventDateTime' => $row['event_date_time'] ? (string)$row['event_date_time'] : null,
            'endDateTime' => $row['end_date_time'] ? (string)$row['end_date_time'] : null,
            'dueDateTimestamp' => $row['due_date_timestamp'] ? (string)$row['due_date_timestamp'] : null,
            'createdTimestamp' => (string)$row['created_timestamp'],
        ];
    }
}
