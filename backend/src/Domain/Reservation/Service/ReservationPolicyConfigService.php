<?php

namespace App\Domain\Reservation\Service;

use App\Shared\Exceptions\DomainValidationException;
use App\Shared\Utils\AppClock;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class ReservationPolicyConfigService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getBookingWindow(): array
    {
        $this->ensurePolicyTablesReady();

        $row = $this->connection->fetchAssociative(
            'SELECT active_booking_start_date,
                    active_booking_end_date,
                    extended_booking_end_date,
                    term_label,
                    allow_exemptions,
                    exemption_keywords_json,
                    restricted_venue_keywords_json
             FROM reservation_booking_windows
             WHERE is_active = TRUE
             ORDER BY booking_window_identifier DESC
             LIMIT 1'
        );

        if ($row === false || $row === []) {
            return $this->buildDefaultBookingWindow();
        }

        $defaultWindow = $this->buildDefaultBookingWindow();

        return [
            'activeBookingStartDate' => $this->normalizeDateString($row['active_booking_start_date'] ?? null) ?? $defaultWindow['activeBookingStartDate'],
            'activeBookingEndDate' => $this->normalizeDateString($row['active_booking_end_date'] ?? null) ?? $defaultWindow['activeBookingEndDate'],
            'extendedBookingEndDate' => $this->normalizeDateString($row['extended_booking_end_date'] ?? null) ?? $defaultWindow['extendedBookingEndDate'],
            'termLabel' => trim((string) ($row['term_label'] ?? '')) ?: $defaultWindow['termLabel'],
            'allowExemptions' => filter_var($row['allow_exemptions'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
            'exemptionKeywords' => $this->decodeStringArray($row['exemption_keywords_json'] ?? null, $defaultWindow['exemptionKeywords']),
            'restrictedVenueKeywords' => $this->decodeStringArray($row['restricted_venue_keywords_json'] ?? null, $defaultWindow['restrictedVenueKeywords']),
        ];
    }

    public function saveBookingWindow(array $payload): array
    {
        $this->ensurePolicyTablesReady();

        $normalizedWindow = $this->normalizeBookingWindowPayload($payload);

        $this->connection->beginTransaction();
        try {
            $this->connection->executeStatement('UPDATE reservation_booking_windows SET is_active = FALSE WHERE is_active = TRUE');
            $this->connection->insert('reservation_booking_windows', [
                'active_booking_start_date' => $normalizedWindow['activeBookingStartDate'],
                'active_booking_end_date' => $normalizedWindow['activeBookingEndDate'],
                'extended_booking_end_date' => $normalizedWindow['extendedBookingEndDate'],
                'term_label' => $normalizedWindow['termLabel'],
                'allow_exemptions' => $normalizedWindow['allowExemptions'],
                'exemption_keywords_json' => json_encode($normalizedWindow['exemptionKeywords'], JSON_THROW_ON_ERROR),
                'restricted_venue_keywords_json' => json_encode($normalizedWindow['restrictedVenueKeywords'], JSON_THROW_ON_ERROR),
                'is_active' => true,
            ], [
                'active_booking_start_date' => ParameterType::STRING,
                'active_booking_end_date' => ParameterType::STRING,
                'extended_booking_end_date' => ParameterType::STRING,
                'term_label' => ParameterType::STRING,
                'allow_exemptions' => ParameterType::BOOLEAN,
                'exemption_keywords_json' => ParameterType::STRING,
                'restricted_venue_keywords_json' => ParameterType::STRING,
                'is_active' => ParameterType::BOOLEAN,
            ]);
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        return $normalizedWindow;
    }

    public function listClassScheduleBlocks(?int $venueIdentifier = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $this->ensurePolicyTablesReady();

        $params = [];
        $types = [];
        $whereParts = [];

        if ($venueIdentifier !== null && $venueIdentifier > 0) {
            $whereParts[] = 'venue_identifier = :venueIdentifier';
            $params['venueIdentifier'] = $venueIdentifier;
            $types['venueIdentifier'] = ParameterType::INTEGER;
        }

        $normalizedDateFrom = $this->normalizeDateString($dateFrom);
        if ($normalizedDateFrom !== null) {
            $whereParts[] = 'block_date >= :dateFrom';
            $params['dateFrom'] = $normalizedDateFrom;
            $types['dateFrom'] = ParameterType::STRING;
        }

        $normalizedDateTo = $this->normalizeDateString($dateTo);
        if ($normalizedDateTo !== null) {
            $whereParts[] = 'block_date <= :dateTo';
            $params['dateTo'] = $normalizedDateTo;
            $types['dateTo'] = ParameterType::STRING;
        }

        $where = $whereParts === [] ? '' : ('WHERE ' . implode(' AND ', $whereParts));

        $rows = $this->connection->fetchAllAssociative(
            "SELECT schedule_block_identifier,
                    venue_identifier,
                    block_date,
                    start_time,
                    end_time,
                    block_label,
                    block_type
             FROM venue_schedule_blocks
             {$where}
             ORDER BY block_date ASC, start_time ASC, schedule_block_identifier ASC",
            $params,
            $types
        );

        return array_map([$this, 'mapScheduleBlockRow'], $rows);
    }

    public function createClassScheduleBlock(array $payload): array
    {
        $this->ensurePolicyTablesReady();

        $normalizedBlock = $this->normalizeScheduleBlockPayload($payload);

        $this->connection->insert('venue_schedule_blocks', [
            'venue_identifier' => $normalizedBlock['venueIdentifier'],
            'block_date' => $normalizedBlock['blockDate'],
            'start_time' => $normalizedBlock['startTime'],
            'end_time' => $normalizedBlock['endTime'],
            'block_label' => $normalizedBlock['blockLabel'],
            'block_type' => $normalizedBlock['blockType'],
        ], [
            'venue_identifier' => ParameterType::INTEGER,
            'block_date' => ParameterType::STRING,
            'start_time' => ParameterType::STRING,
            'end_time' => ParameterType::STRING,
            'block_label' => ParameterType::STRING,
            'block_type' => ParameterType::STRING,
        ]);

        $identifier = (int) $this->connection->lastInsertId();
        return [
            'scheduleBlockIdentifier' => $identifier,
            ...$normalizedBlock,
        ];
    }

    public function deleteClassScheduleBlock(int $scheduleBlockIdentifier): void
    {
        $this->ensurePolicyTablesReady();
        $this->connection->delete('venue_schedule_blocks', [
            'schedule_block_identifier' => $scheduleBlockIdentifier,
        ], [
            'schedule_block_identifier' => ParameterType::INTEGER,
        ]);
    }

    public function findScheduleBlocksOverlappingRange(array $venueIdentifiers, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd): array
    {
        $this->ensurePolicyTablesReady();

        $normalizedVenueIdentifiers = array_values(array_filter(
            array_map(static fn ($venueIdentifier): int => (int) $venueIdentifier, $venueIdentifiers),
            static fn (int $venueIdentifier): bool => $venueIdentifier > 0
        ));

        if ($normalizedVenueIdentifiers === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT schedule_block_identifier,
                    venue_identifier,
                    block_date,
                    start_time,
                    end_time,
                    block_label,
                    block_type
             FROM venue_schedule_blocks
             WHERE venue_identifier IN (:venueIdentifiers)
               AND block_date BETWEEN :dateFrom AND :dateTo
             ORDER BY block_date ASC, start_time ASC, schedule_block_identifier ASC',
            [
                'venueIdentifiers' => $normalizedVenueIdentifiers,
                'dateFrom' => $rangeStart->format('Y-m-d'),
                'dateTo' => $rangeEnd->format('Y-m-d'),
            ],
            [
                'venueIdentifiers' => Connection::PARAM_INT_ARRAY,
                'dateFrom' => ParameterType::STRING,
                'dateTo' => ParameterType::STRING,
            ]
        );

        $rangeStartValue = \DateTimeImmutable::createFromInterface($rangeStart)->setTimezone(AppClock::timezone());
        $rangeEndValue = \DateTimeImmutable::createFromInterface($rangeEnd)->setTimezone(AppClock::timezone());

        return array_values(array_filter(
            array_map([$this, 'mapScheduleBlockRow'], $rows),
            static function (array $scheduleBlock) use ($rangeStartValue, $rangeEndValue): bool {
                $blockStart = new \DateTimeImmutable(sprintf('%s %s', $scheduleBlock['blockDate'], $scheduleBlock['startTime']), AppClock::timezone());
                $blockEnd = new \DateTimeImmutable(sprintf('%s %s', $scheduleBlock['blockDate'], $scheduleBlock['endTime']), AppClock::timezone());

                return $blockStart < $rangeEndValue && $blockEnd > $rangeStartValue;
            }
        ));
    }

    private function ensurePolicyTablesReady(): void
    {
        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS reservation_booking_windows (
                booking_window_identifier INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
                active_booking_start_date DATE NOT NULL,
                active_booking_end_date DATE NOT NULL,
                extended_booking_end_date DATE DEFAULT NULL,
                term_label VARCHAR(120) DEFAULT NULL,
                allow_exemptions BOOLEAN NOT NULL DEFAULT TRUE,
                exemption_keywords_json JSON DEFAULT NULL,
                restricted_venue_keywords_json JSON DEFAULT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_timestamp TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS venue_schedule_blocks (
                schedule_block_identifier INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
                venue_identifier INT NOT NULL,
                block_date DATE NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                block_label VARCHAR(200) NOT NULL,
                block_type VARCHAR(80) NOT NULL DEFAULT \'Class Schedule\',
                created_timestamp TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    private function normalizeBookingWindowPayload(array $payload): array
    {
        $defaultWindow = $this->buildDefaultBookingWindow();
        $activeBookingStartDate = $this->normalizeDateString($payload['activeBookingStartDate'] ?? null) ?? $defaultWindow['activeBookingStartDate'];
        $activeBookingEndDate = $this->normalizeDateString($payload['activeBookingEndDate'] ?? null) ?? $defaultWindow['activeBookingEndDate'];
        $extendedBookingEndDate = $this->normalizeDateString($payload['extendedBookingEndDate'] ?? null) ?? $defaultWindow['extendedBookingEndDate'];
        $termLabel = trim((string) ($payload['termLabel'] ?? $defaultWindow['termLabel']));
        $allowExemptions = filter_var($payload['allowExemptions'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $allowExemptions = $allowExemptions ?? true;
        $exemptionKeywords = $this->normalizeStringArray($payload['exemptionKeywords'] ?? $defaultWindow['exemptionKeywords']);
        $restrictedVenueKeywords = $this->normalizeStringArray($payload['restrictedVenueKeywords'] ?? $defaultWindow['restrictedVenueKeywords']);

        if ($activeBookingEndDate < $activeBookingStartDate) {
            throw new DomainValidationException('The active booking end date must be on or after the start date.');
        }

        if ($extendedBookingEndDate < $activeBookingEndDate) {
            throw new DomainValidationException('The extended booking end date must be on or after the active booking end date.');
        }

        return [
            'activeBookingStartDate' => $activeBookingStartDate,
            'activeBookingEndDate' => $activeBookingEndDate,
            'extendedBookingEndDate' => $extendedBookingEndDate,
            'termLabel' => $termLabel,
            'allowExemptions' => $allowExemptions,
            'exemptionKeywords' => $exemptionKeywords,
            'restrictedVenueKeywords' => $restrictedVenueKeywords,
        ];
    }

    private function normalizeScheduleBlockPayload(array $payload): array
    {
        $venueIdentifier = (int) ($payload['venueIdentifier'] ?? 0);
        $blockDate = $this->normalizeDateString($payload['blockDate'] ?? null);
        $startTime = $this->normalizeTimeString($payload['startTime'] ?? null);
        $endTime = $this->normalizeTimeString($payload['endTime'] ?? null);
        $blockLabel = trim((string) ($payload['blockLabel'] ?? ''));
        $blockType = trim((string) ($payload['blockType'] ?? 'Class Schedule')) ?: 'Class Schedule';

        if ($venueIdentifier <= 0) {
            throw new DomainValidationException('A valid venue identifier is required for a class schedule block.');
        }

        if ($blockDate === null) {
            throw new DomainValidationException('A valid block date is required.');
        }

        if ($startTime === null || $endTime === null) {
            throw new DomainValidationException('Valid class schedule start and end times are required.');
        }

        if ($endTime <= $startTime) {
            throw new DomainValidationException('The class schedule end time must be later than the start time.');
        }

        if ($blockLabel === '') {
            throw new DomainValidationException('A class schedule label is required.');
        }

        return [
            'venueIdentifier' => $venueIdentifier,
            'blockDate' => $blockDate,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'blockLabel' => $blockLabel,
            'blockType' => $blockType,
        ];
    }

    private function buildDefaultBookingWindow(): array
    {
        $today = AppClock::now()->setTime(0, 0);
        $month = (int) $today->format('n');
        $year = (int) $today->format('Y');

        if ($month >= 1 && $month <= 5) {
            $activeBookingEndDate = sprintf('%d-05-31', $year);
            $extendedBookingEndDate = sprintf('%d-07-31', $year);
            $termLabel = sprintf('Second Semester %d', $year);
        } elseif ($month >= 6 && $month <= 7) {
            $activeBookingEndDate = sprintf('%d-07-31', $year);
            $extendedBookingEndDate = sprintf('%d-12-31', $year);
            $termLabel = sprintf('Summer Term %d', $year);
        } else {
            $activeBookingEndDate = sprintf('%d-12-31', $year);
            $extendedBookingEndDate = sprintf('%d-05-31', $year + 1);
            $termLabel = sprintf('First Semester %d', $year);
        }

        return [
            'activeBookingStartDate' => $today->format('Y-m-d'),
            'activeBookingEndDate' => $activeBookingEndDate,
            'extendedBookingEndDate' => $extendedBookingEndDate,
            'termLabel' => $termLabel,
            'allowExemptions' => true,
            'exemptionKeywords' => [
                'rso',
                'registered student organization',
                'institutional',
                'institution event',
                'institutional event',
                'university event',
                'school-wide',
                'school wide',
            ],
            'restrictedVenueKeywords' => [
                'classroom',
                'class room',
                'avr',
                'case room',
                'caseroom',
            ],
        ];
    }

    private function mapScheduleBlockRow(array $row): array
    {
        return [
            'scheduleBlockIdentifier' => (int) ($row['schedule_block_identifier'] ?? 0),
            'venueIdentifier' => (int) ($row['venue_identifier'] ?? 0),
            'blockDate' => $this->normalizeDateString($row['block_date'] ?? null) ?? '',
            'startTime' => substr((string) ($row['start_time'] ?? ''), 0, 5),
            'endTime' => substr((string) ($row['end_time'] ?? ''), 0, 5),
            'blockLabel' => trim((string) ($row['block_label'] ?? '')),
            'blockType' => trim((string) ($row['block_type'] ?? 'Class Schedule')) ?: 'Class Schedule',
        ];
    }

    private function normalizeDateString(mixed $value): ?string
    {
        $normalizedValue = trim((string) ($value ?? ''));
        if ($normalizedValue === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($normalizedValue, AppClock::timezone()))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeTimeString(mixed $value): ?string
    {
        $normalizedValue = trim((string) ($value ?? ''));
        if ($normalizedValue === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable(sprintf('1970-01-01 %s', $normalizedValue), AppClock::timezone()))->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function decodeStringArray(mixed $value, array $fallback): array
    {
        if (!is_string($value) || trim($value) === '') {
            return $fallback;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            return $this->normalizeStringArray($decoded);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function normalizeStringArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalizedValues = array_values(array_unique(array_filter(array_map(
            static fn ($item): string => strtolower(trim((string) $item)),
            $value
        ))));

        return $normalizedValues;
    }
}
