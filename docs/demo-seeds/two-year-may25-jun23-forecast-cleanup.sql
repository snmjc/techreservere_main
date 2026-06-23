-- Cleanup for docs/demo-seeds/two-year-may25-jun23-forecast.sql.
--
-- Run from the repo root while Docker is running:
--   Get-Content -Raw docs/demo-seeds/two-year-may25-jun23-forecast-cleanup.sql | docker compose -f compose.dev.yml exec -T database psql -U techreserve_user -d techreserve

BEGIN;

CREATE TABLE IF NOT EXISTS demo_seed_equipment_backup (
    equipment_name VARCHAR(150) PRIMARY KEY,
    equipment_category VARCHAR(100) NOT NULL,
    total_quantity INT NOT NULL,
    available_quantity INT NOT NULL,
    operational_status VARCHAR(50) NOT NULL,
    equipment_state VARCHAR(50) NOT NULL,
    description TEXT,
    equipment_brand VARCHAR(100) NOT NULL,
    barcode VARCHAR(120) NOT NULL,
    asset_id VARCHAR(13) NOT NULL,
    serial_number VARCHAR(120) NOT NULL,
    updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL
);

DELETE FROM reservations
WHERE reservation_code LIKE 'DEMO-%';

DELETE FROM accounts
WHERE email_address IN (
    'analytics.demo.borrower@techreserve.local',
    'research.demo.borrower@techreserve.local'
);

UPDATE equipment
SET
    equipment_category = backup.equipment_category,
    total_quantity = backup.total_quantity,
    available_quantity = backup.available_quantity,
    operational_status = backup.operational_status,
    equipment_state = backup.equipment_state,
    description = backup.description,
    equipment_brand = backup.equipment_brand,
    barcode = backup.barcode,
    asset_id = backup.asset_id,
    serial_number = backup.serial_number,
    updated_at = NOW()
FROM demo_seed_equipment_backup AS backup
WHERE equipment.equipment_name = backup.equipment_name;

DELETE FROM equipment
WHERE asset_id IN ('DEM-AUD-001', 'DEM-CMP-001')
  AND NOT EXISTS (
      SELECT 1
      FROM demo_seed_equipment_backup AS backup
      WHERE backup.equipment_name = equipment.equipment_name
  );

DROP TABLE IF EXISTS demo_seed_equipment_backup;

COMMIT;
