<?php

namespace App\Domain\Equipment\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class EquipmentInventoryUnitService
{
    private bool $schemaReady = false;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function ensureSchemaReady(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $equipmentColumns = $this->connection->fetchFirstColumn(
            "SELECT column_name
             FROM information_schema.columns
             WHERE table_schema = CURRENT_SCHEMA()
               AND table_name = 'equipment'"
        );

        if ($equipmentColumns !== []) {
            $runtimeColumns = [
                'equipment_model' => 'ALTER TABLE equipment ADD COLUMN equipment_model VARCHAR(160) DEFAULT NULL',
                'remarks' => 'ALTER TABLE equipment ADD COLUMN remarks TEXT DEFAULT NULL',
                'specifications_json' => 'ALTER TABLE equipment ADD COLUMN specifications_json JSONB DEFAULT NULL',
            ];

            foreach ($runtimeColumns as $columnName => $statement) {
                if (!in_array($columnName, $equipmentColumns, true)) {
                    $this->connection->executeStatement($statement);
                }
            }
        }

        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS equipment_units (
                equipment_unit_identifier SERIAL PRIMARY KEY,
                equipment_identifier INT NOT NULL REFERENCES equipment(equipment_identifier) ON DELETE CASCADE,
                unit_code VARCHAR(120) NOT NULL,
                barcode VARCHAR(120) DEFAULT NULL,
                asset_tag VARCHAR(120) DEFAULT NULL,
                serial_number VARCHAR(160) DEFAULT NULL,
                condition_status VARCHAR(60) NOT NULL DEFAULT \'Good\',
                availability_status VARCHAR(60) NOT NULL DEFAULT \'Available\',
                storage_location VARCHAR(160) DEFAULT NULL,
                date_acquired DATE DEFAULT NULL,
                warranty_details TEXT DEFAULT NULL,
                specifications_json JSONB DEFAULT NULL,
                remarks TEXT DEFAULT NULL,
                maintenance_state VARCHAR(60) NOT NULL DEFAULT \'Operational\',
                retired_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NULL,
                lost_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NULL,
                created_timestamp TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_timestamp TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->connection->executeStatement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_equipment_units_unit_code ON equipment_units (unit_code)');
        $this->connection->executeStatement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_equipment_units_barcode ON equipment_units (barcode) WHERE barcode IS NOT NULL');
        $this->connection->executeStatement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_equipment_units_asset_tag ON equipment_units (asset_tag) WHERE asset_tag IS NOT NULL');
        $this->connection->executeStatement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_equipment_units_serial_number ON equipment_units (serial_number) WHERE serial_number IS NOT NULL');

        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS reservation_equipment_units (
                reservation_equipment_unit_identifier SERIAL PRIMARY KEY,
                reservation_identifier INT NOT NULL REFERENCES reservations(reservation_identifier) ON DELETE CASCADE,
                equipment_identifier INT NOT NULL REFERENCES equipment(equipment_identifier) ON DELETE CASCADE,
                equipment_unit_identifier INT NOT NULL REFERENCES equipment_units(equipment_unit_identifier) ON DELETE CASCADE,
                allocation_status VARCHAR(40) NOT NULL DEFAULT \'reserved\',
                created_timestamp TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_timestamp TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->schemaReady = true;
        $this->migrateLegacyAggregateInventory();
    }

    public function fetchParentMetadataByEquipmentIds(array $equipmentIdentifiers): array
    {
        $equipmentIdentifiers = array_values(array_filter(array_map('intval', $equipmentIdentifiers), static fn (int $value): bool => $value > 0));
        if ($equipmentIdentifiers === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT equipment_identifier, equipment_model, remarks, specifications_json
             FROM equipment
             WHERE equipment_identifier IN (:equipmentIdentifiers)',
            ['equipmentIdentifiers' => $equipmentIdentifiers],
            ['equipmentIdentifiers' => ArrayParameterType::INTEGER]
        );

        $metadataByEquipment = [];
        foreach ($rows as $row) {
            $metadataByEquipment[(int) $row['equipment_identifier']] = [
                'equipmentModel' => (string) ($row['equipment_model'] ?? ''),
                'remarks' => $row['remarks'] === null ? null : (string) $row['remarks'],
                'specifications' => $this->decodeJsonColumn($row['specifications_json'] ?? null),
            ];
        }

        return $metadataByEquipment;
    }

    public function updateParentMetadata(int $equipmentIdentifier, array $payload): void
    {
        $this->connection->executeStatement(
            'UPDATE equipment
             SET equipment_model = :equipmentModel,
                 remarks = :remarks,
                 specifications_json = :specificationsJson
             WHERE equipment_identifier = :equipmentIdentifier',
            [
                'equipmentModel' => $this->normalizeNullableString($payload['equipmentModel'] ?? null),
                'remarks' => $this->normalizeNullableString($payload['remarks'] ?? null),
                'specificationsJson' => $this->encodeJsonColumn($payload['specifications'] ?? null),
                'equipmentIdentifier' => $equipmentIdentifier,
            ],
            [
                'equipmentModel' => ParameterType::STRING,
                'remarks' => ParameterType::STRING,
                'specificationsJson' => ParameterType::STRING,
                'equipmentIdentifier' => ParameterType::INTEGER,
            ]
        );
    }

    public function saveUnitsForEquipment(int $equipmentIdentifier, string $equipmentCategory, array $unitRecords, array $legacyFallback = []): void
    {
        $normalizedUnits = $this->normalizeUnitPayload($equipmentIdentifier, $equipmentCategory, $unitRecords, $legacyFallback);

        $this->connection->transactional(function () use ($equipmentIdentifier, $normalizedUnits): void {
            $this->connection->executeStatement(
                'DELETE FROM equipment_units WHERE equipment_identifier = :equipmentIdentifier',
                ['equipmentIdentifier' => $equipmentIdentifier],
                ['equipmentIdentifier' => ParameterType::INTEGER]
            );

            foreach ($normalizedUnits as $unit) {
                $this->connection->insert(
                    'equipment_units',
                    [
                        'equipment_identifier' => $equipmentIdentifier,
                        'unit_code' => $unit['unitCode'],
                        'barcode' => $unit['barcode'],
                        'asset_tag' => $unit['assetTag'],
                        'serial_number' => $unit['serialNumber'],
                        'condition_status' => $unit['conditionStatus'],
                        'availability_status' => $unit['availabilityStatus'],
                        'storage_location' => $unit['storageLocation'],
                        'date_acquired' => $unit['dateAcquired'],
                        'warranty_details' => $unit['warrantyDetails'],
                        'specifications_json' => $this->encodeJsonColumn($unit['specifications']),
                        'remarks' => $unit['remarks'],
                        'maintenance_state' => $unit['maintenanceState'],
                        'retired_at' => $unit['retiredAt'],
                        'lost_at' => $unit['lostAt'],
                        'created_timestamp' => $this->now(),
                        'updated_timestamp' => $this->now(),
                    ]
                );
            }
        });

        $this->recalculateSummary($equipmentIdentifier);
    }

    public function fetchUnitsByEquipmentIds(array $equipmentIdentifiers): array
    {
        $equipmentIdentifiers = array_values(array_filter(array_map('intval', $equipmentIdentifiers), static fn (int $value): bool => $value > 0));
        if ($equipmentIdentifiers === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT equipment_unit_identifier, equipment_identifier, unit_code, barcode, asset_tag, serial_number,
                    condition_status, availability_status, storage_location, date_acquired, warranty_details,
                    specifications_json, remarks, maintenance_state, retired_at, lost_at, created_timestamp, updated_timestamp
             FROM equipment_units
             WHERE equipment_identifier IN (:equipmentIdentifiers)
             ORDER BY equipment_identifier ASC, equipment_unit_identifier ASC',
            ['equipmentIdentifiers' => $equipmentIdentifiers],
            ['equipmentIdentifiers' => ArrayParameterType::INTEGER]
        );

        $unitsByEquipment = [];
        foreach ($rows as $row) {
            $equipmentIdentifier = (int) $row['equipment_identifier'];
            $unitsByEquipment[$equipmentIdentifier] ??= [];
            $unitsByEquipment[$equipmentIdentifier][] = [
                'equipmentUnitIdentifier' => (int) $row['equipment_unit_identifier'],
                'equipmentIdentifier' => $equipmentIdentifier,
                'equipmentUnitIdentifierCode' => (string) $row['unit_code'],
                'barcode' => $row['barcode'] === null ? null : (string) $row['barcode'],
                'assetTag' => $row['asset_tag'] === null ? null : (string) $row['asset_tag'],
                'serialNumber' => $row['serial_number'] === null ? null : (string) $row['serial_number'],
                'conditionStatus' => (string) $row['condition_status'],
                'availabilityStatus' => (string) $row['availability_status'],
                'storageLocation' => $row['storage_location'] === null ? null : (string) $row['storage_location'],
                'dateAcquired' => $row['date_acquired'] === null ? null : (string) $row['date_acquired'],
                'warrantyDetails' => $row['warranty_details'] === null ? null : (string) $row['warranty_details'],
                'specifications' => $this->decodeJsonColumn($row['specifications_json'] ?? null),
                'remarks' => $row['remarks'] === null ? null : (string) $row['remarks'],
                'maintenanceState' => (string) $row['maintenance_state'],
                'retiredAt' => $this->formatTimestamp($row['retired_at'] ?? null),
                'lostAt' => $this->formatTimestamp($row['lost_at'] ?? null),
                'createdTimestamp' => $this->formatTimestamp($row['created_timestamp'] ?? null),
                'updatedTimestamp' => $this->formatTimestamp($row['updated_timestamp'] ?? null),
            ];
        }

        return $unitsByEquipment;
    }

    public function fetchDerivedCountsByEquipmentIds(array $equipmentIdentifiers): array
    {
        $equipmentIdentifiers = array_values(array_filter(array_map('intval', $equipmentIdentifiers), static fn (int $value): bool => $value > 0));
        if ($equipmentIdentifiers === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT equipment_identifier,
                    COUNT(*) AS total_count,
                    COUNT(*) FILTER (WHERE availability_status = \'Available\' AND retired_at IS NULL AND lost_at IS NULL) AS available_count,
                    COUNT(*) FILTER (WHERE availability_status = \'Reserved\') AS reserved_count,
                    COUNT(*) FILTER (WHERE availability_status IN (\'Borrowed\', \'Released\', \'Active\')) AS borrowed_count,
                    COUNT(*) FILTER (WHERE availability_status IN (\'Under Maintenance\', \'Maintenance\') OR maintenance_state IN (\'Under Maintenance\', \'Maintenance\')) AS maintenance_count,
                    COUNT(*) FILTER (WHERE availability_status IN (\'Unavailable\', \'Retired\', \'Lost\') OR retired_at IS NOT NULL OR lost_at IS NOT NULL) AS unavailable_count
             FROM equipment_units
             WHERE equipment_identifier IN (:equipmentIdentifiers)
             GROUP BY equipment_identifier',
            ['equipmentIdentifiers' => $equipmentIdentifiers],
            ['equipmentIdentifiers' => ArrayParameterType::INTEGER]
        );

        $countsByEquipment = [];
        foreach ($rows as $row) {
            $countsByEquipment[(int) $row['equipment_identifier']] = [
                'total' => (int) $row['total_count'],
                'available' => (int) $row['available_count'],
                'reserved' => (int) $row['reserved_count'],
                'borrowed' => (int) $row['borrowed_count'],
                'underMaintenance' => (int) $row['maintenance_count'],
                'unavailable' => (int) $row['unavailable_count'],
            ];
        }

        return $countsByEquipment;
    }

    public function recalculateSummary(int $equipmentIdentifier): array
    {
        $counts = $this->fetchDerivedCountsByEquipmentIds([$equipmentIdentifier])[$equipmentIdentifier] ?? [
            'total' => 0,
            'available' => 0,
            'reserved' => 0,
            'borrowed' => 0,
            'underMaintenance' => 0,
            'unavailable' => 0,
        ];

        $operationalStatus = 'Unavailable';
        $equipmentState = 'Unavailable';
        if ($counts['underMaintenance'] > 0 && $counts['available'] === 0) {
            $operationalStatus = 'Under Maintenance';
            $equipmentState = 'Under Maintenance';
        } elseif ($counts['available'] > 0) {
            $operationalStatus = 'Available';
            $equipmentState = 'Available';
        } elseif ($counts['total'] > 0 && $counts['unavailable'] >= $counts['total']) {
            $operationalStatus = 'Unavailable';
            $equipmentState = 'Unavailable';
        }

        $this->connection->executeStatement(
            'UPDATE equipment
             SET total_quantity = :totalQuantity,
                 available_quantity = :availableQuantity,
                 operational_status = :operationalStatus,
                 equipment_state = :equipmentState,
                 updated_at = CURRENT_TIMESTAMP
             WHERE equipment_identifier = :equipmentIdentifier',
            [
                'totalQuantity' => $counts['total'],
                'availableQuantity' => $counts['available'],
                'operationalStatus' => $operationalStatus,
                'equipmentState' => $equipmentState,
                'equipmentIdentifier' => $equipmentIdentifier,
            ],
            [
                'totalQuantity' => ParameterType::INTEGER,
                'availableQuantity' => ParameterType::INTEGER,
                'operationalStatus' => ParameterType::STRING,
                'equipmentState' => ParameterType::STRING,
                'equipmentIdentifier' => ParameterType::INTEGER,
            ]
        );

        return $counts;
    }

    private function migrateLegacyAggregateInventory(): void
    {
        $legacyRows = $this->connection->fetchAllAssociative(
            'SELECT equipment_identifier, equipment_category, total_quantity, available_quantity,
                    equipment_state, operational_status, barcode, asset_id, description
             FROM equipment
             ORDER BY equipment_identifier ASC'
        );

        foreach ($legacyRows as $row) {
            $equipmentIdentifier = (int) $row['equipment_identifier'];
            $existingUnitCount = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM equipment_units WHERE equipment_identifier = :equipmentIdentifier',
                ['equipmentIdentifier' => $equipmentIdentifier],
                ['equipmentIdentifier' => ParameterType::INTEGER]
            );

            if ($existingUnitCount > 0) {
                $this->recalculateSummary($equipmentIdentifier);
                continue;
            }

            $quantity = max(
                1,
                (int) ($row['total_quantity'] ?? 0),
                (int) ($row['available_quantity'] ?? 0)
            );

            $generatedUnits = [];
            for ($index = 0; $index < $quantity; $index++) {
                $generatedUnits[] = [
                    'equipmentUnitIdentifierCode' => $this->buildUnitCode($equipmentIdentifier, $index + 1),
                    'barcode' => $index === 0 ? $this->normalizeNullableString($row['barcode'] ?? null) : null,
                    'assetTag' => $index === 0 ? $this->normalizeNullableString($row['asset_id'] ?? null) : null,
                    'serialNumber' => $index === 0 ? $this->normalizeNullableString($row['asset_id'] ?? null) : null,
                    'conditionStatus' => 'Good',
                    'availabilityStatus' => $this->deriveLegacyAvailabilityStatus((string) ($row['operational_status'] ?? $row['equipment_state'] ?? 'Available')),
                    'storageLocation' => null,
                    'dateAcquired' => null,
                    'warrantyDetails' => null,
                    'specifications' => null,
                    'remarks' => $this->normalizeNullableString($row['description'] ?? null),
                    'maintenanceState' => (string) ($row['equipment_state'] ?? 'Operational'),
                    'retiredAt' => null,
                    'lostAt' => null,
                ];
            }

            $this->saveUnitsForEquipment(
                $equipmentIdentifier,
                (string) ($row['equipment_category'] ?? ''),
                $generatedUnits,
                []
            );
        }
    }

    private function normalizeUnitPayload(int $equipmentIdentifier, string $equipmentCategory, array $unitRecords, array $legacyFallback): array
    {
        $units = [];
        foreach ($unitRecords as $index => $unitRecord) {
            $unitCode = trim((string) ($unitRecord['equipmentUnitIdentifierCode'] ?? $unitRecord['unitCode'] ?? ''));
            if ($unitCode === '') {
                $unitCode = $this->buildUnitCode($equipmentIdentifier, $index + 1);
            }

            $barcode = $this->normalizeNullableString($unitRecord['barcode'] ?? null);
            $assetTag = $this->normalizeNullableString($unitRecord['assetTag'] ?? null);
            $serialNumber = $this->normalizeNullableString($unitRecord['serialNumber'] ?? null);
            $units[] = [
                'unitCode' => $unitCode,
                'barcode' => $barcode,
                'assetTag' => $assetTag,
                'serialNumber' => $serialNumber,
                'conditionStatus' => trim((string) ($unitRecord['conditionStatus'] ?? 'Good')) ?: 'Good',
                'availabilityStatus' => trim((string) ($unitRecord['availabilityStatus'] ?? $legacyFallback['availabilityStatus'] ?? 'Available')) ?: 'Available',
                'storageLocation' => $this->normalizeNullableString($unitRecord['storageLocation'] ?? $legacyFallback['storageLocation'] ?? null),
                'dateAcquired' => $this->normalizeNullableString($unitRecord['dateAcquired'] ?? null),
                'warrantyDetails' => $this->normalizeNullableString($unitRecord['warrantyDetails'] ?? null),
                'specifications' => $this->normalizeArrayOrNull($unitRecord['specifications'] ?? null),
                'remarks' => $this->normalizeNullableString($unitRecord['remarks'] ?? $legacyFallback['remarks'] ?? null),
                'maintenanceState' => trim((string) ($unitRecord['maintenanceState'] ?? 'Operational')) ?: 'Operational',
                'retiredAt' => $this->normalizeNullableString($unitRecord['retiredAt'] ?? null),
                'lostAt' => $this->normalizeNullableString($unitRecord['lostAt'] ?? null),
            ];
        }

        if ($units === []) {
            $units[] = [
                'unitCode' => $this->buildUnitCode($equipmentIdentifier, 1),
                'barcode' => null,
                'assetTag' => null,
                'serialNumber' => null,
                'conditionStatus' => 'Good',
                'availabilityStatus' => 'Available',
                'storageLocation' => null,
                'dateAcquired' => null,
                'warrantyDetails' => null,
                'specifications' => null,
                'remarks' => $this->normalizeNullableString($legacyFallback['remarks'] ?? null),
                'maintenanceState' => 'Operational',
                'retiredAt' => null,
                'lostAt' => null,
            ];
        }

        $this->assertUniqueUnitIdentifiers($equipmentIdentifier, $units);

        return $units;
    }

    private function assertUniqueUnitIdentifiers(int $equipmentIdentifier, array $units): void
    {
        $seenIdentifiers = [];
        foreach ($units as $unit) {
            foreach (['barcode', 'assetTag', 'serialNumber', 'unitCode'] as $key) {
                $value = trim((string) ($unit[$key] ?? ''));
                if ($value === '') {
                    continue;
                }

                $fingerprint = $key . ':' . strtolower($value);
                if (isset($seenIdentifiers[$fingerprint])) {
                    throw new \InvalidArgumentException(sprintf('Duplicate %s found in unit editor: %s', $key, $value));
                }

                $seenIdentifiers[$fingerprint] = true;
            }
        }

        $barcodes = array_values(array_filter(array_map(static fn (array $unit): string => trim((string) ($unit['barcode'] ?? '')), $units)));
        $assetTags = array_values(array_filter(array_map(static fn (array $unit): string => trim((string) ($unit['assetTag'] ?? '')), $units)));
        $serialNumbers = array_values(array_filter(array_map(static fn (array $unit): string => trim((string) ($unit['serialNumber'] ?? '')), $units)));
        $unitCodes = array_values(array_filter(array_map(static fn (array $unit): string => trim((string) ($unit['unitCode'] ?? '')), $units)));

        $existingRows = $this->connection->fetchAllAssociative(
            'SELECT barcode, asset_tag, serial_number, unit_code
             FROM equipment_units
             WHERE equipment_identifier <> :equipmentIdentifier
               AND (
                    barcode IN (:barcodes)
                 OR asset_tag IN (:assetTags)
                 OR serial_number IN (:serialNumbers)
                 OR unit_code IN (:unitCodes)
               )',
            [
                'equipmentIdentifier' => $equipmentIdentifier,
                'barcodes' => $barcodes === [] ? [''] : $barcodes,
                'assetTags' => $assetTags === [] ? [''] : $assetTags,
                'serialNumbers' => $serialNumbers === [] ? [''] : $serialNumbers,
                'unitCodes' => $unitCodes === [] ? [''] : $unitCodes,
            ],
            [
                'equipmentIdentifier' => ParameterType::INTEGER,
                'barcodes' => ArrayParameterType::STRING,
                'assetTags' => ArrayParameterType::STRING,
                'serialNumbers' => ArrayParameterType::STRING,
                'unitCodes' => ArrayParameterType::STRING,
            ]
        );

        if ($existingRows !== []) {
            throw new \InvalidArgumentException('A barcode, asset tag, serial number, or unit identifier already exists on another equipment unit.');
        }
    }

    private function deriveLegacyAvailabilityStatus(string $status): string
    {
        $normalizedStatus = strtolower(trim($status));

        return match ($normalizedStatus) {
            'available', 'active' => 'Available',
            'under maintenance', 'maintenance' => 'Under Maintenance',
            'retired' => 'Retired',
            default => 'Unavailable',
        };
    }

    private function buildUnitCode(int $equipmentIdentifier, int $position): string
    {
        return sprintf('EQ-%04d-U%03d', $equipmentIdentifier, $position);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }

    private function normalizeArrayOrNull(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }

    private function encodeJsonColumn(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function decodeJsonColumn(mixed $value): ?array
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private function formatTimestamp(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return (new \DateTimeImmutable($value))->format(\DateTimeInterface::ATOM);
    }
}
