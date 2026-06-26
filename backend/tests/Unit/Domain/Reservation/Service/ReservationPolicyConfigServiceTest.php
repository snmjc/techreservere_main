<?php

namespace App\Tests\Unit\Domain\Reservation\Service;

use App\Domain\Reservation\Service\ReservationPolicyConfigService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ReservationPolicyConfigServiceTest extends TestCase
{
    private Connection|MockObject $connection;
    private ReservationPolicyConfigService $service;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->service = new ReservationPolicyConfigService($this->connection);
    }

    public function testCreateClassScheduleBlockPersistsSingleImportPayload(): void
    {
        $executedStatements = [];

        $this->connection
            ->expects($this->atLeastOnce())
            ->method('executeStatement')
            ->willReturnCallback(static function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->with(
                'venue_schedule_blocks',
                $this->callback(function (array $row): bool {
                    self::assertSame(14, $row['venue_identifier']);
                    self::assertSame('2026-06-27', $row['block_date']);
                    self::assertSame('07:00', $row['start_time']);
                    self::assertSame('09:50', $row['end_time']);
                    self::assertSame('TW21 - CCS0043L - M', $row['block_label']);
                    self::assertSame('Class Schedule', $row['block_type']);
                    self::assertSame('CCS0043L', $row['course_code']);
                    self::assertSame('Capstone Defense', $row['course_name']);
                    self::assertSame('Prof. Cruz', $row['instructor_name']);
                    self::assertNull($row['days_of_week_json']);
                    self::assertSame('2026-06-27', $row['date_range_start']);
                    self::assertSame('2026-06-27', $row['date_range_end']);
                    self::assertSame('F704', $row['venue_name_snapshot']);
                    self::assertSame('2026-2027', $row['academic_year']);
                    self::assertSame('1st Semester', $row['semester_label']);
                    self::assertSame('Imported single-date row', $row['notes']);
                    self::assertSame(40, $row['capacity_limit']);

                    return true;
                }),
                $this->isType('array')
            );

        $this->connection
            ->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('101');

        $result = $this->service->createClassScheduleBlock([
            'venueIdentifier' => 14,
            'venueNameSnapshot' => 'F704',
            'courseCode' => 'CCS0043L',
            'courseName' => 'Capstone Defense',
            'instructorName' => 'Prof. Cruz',
            'blockType' => 'Class Schedule',
            'startTime' => '07:00 AM',
            'endTime' => '09:50 AM',
            'blockDate' => '2026-06-27',
            'academicYear' => '2026-2027',
            'semesterLabel' => '1st Semester',
            'capacityLimit' => 40,
            'notes' => 'Imported single-date row',
            'blockLabel' => 'TW21 - CCS0043L - M',
        ]);

        self::assertSame(101, $result['scheduleBlockIdentifier']);
        self::assertSame('2026-06-27', $result['blockDate']);
        self::assertSame('07:00', $result['startTime']);
        self::assertSame('09:50', $result['endTime']);
        self::assertSame('TW21 - CCS0043L - M', $result['blockLabel']);
        self::assertNotEmpty($executedStatements);
    }

    public function testCreateClassScheduleBlockExpandsRecurringImportPayloadIntoMultipleRows(): void
    {
        $insertedRows = [];

        $this->connection
            ->expects($this->atLeastOnce())
            ->method('executeStatement')
            ->willReturn(0);

        $this->connection
            ->expects($this->exactly(2))
            ->method('insert')
            ->with(
                'venue_schedule_blocks',
                $this->callback(function (array $row) use (&$insertedRows): bool {
                    $insertedRows[] = $row;
                    return true;
                }),
                $this->isType('array')
            );

        $lastInsertIds = ['201', '202'];
        $this->connection
            ->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnCallback(static function () use (&$lastInsertIds): string {
                return array_shift($lastInsertIds) ?? '0';
            });

        $result = $this->service->createClassScheduleBlock([
            'venueIdentifier' => 14,
            'venueNameSnapshot' => 'F704',
            'courseCode' => 'CCS0043L',
            'courseName' => 'Capstone Defense',
            'instructorName' => 'Prof. Cruz',
            'blockType' => 'Class Schedule',
            'startTime' => '07:00 AM',
            'endTime' => '09:50 AM',
            'dateRangeStart' => '2026-06-29',
            'dateRangeEnd' => '2026-07-01',
            'daysOfWeek' => ['Monday', 'Wednesday'],
            'academicYear' => '2026-2027',
            'semesterLabel' => '1st Semester',
            'capacityLimit' => 40,
            'notes' => 'Imported recurring row',
            'blockLabel' => 'TW21 - CCS0043L - M',
        ]);

        self::assertSame(2, $result['createdCount']);
        self::assertCount(2, $result['scheduleBlocks']);
        self::assertSame(['2026-06-29', '2026-07-01'], array_map(
            static fn (array $row): string => $row['block_date'],
            $insertedRows
        ));
        self::assertSame('["Monday","Wednesday"]', $insertedRows[0]['days_of_week_json']);
        self::assertSame('2026-06-29', $insertedRows[0]['date_range_start']);
        self::assertSame('2026-07-01', $insertedRows[0]['date_range_end']);
    }
}
