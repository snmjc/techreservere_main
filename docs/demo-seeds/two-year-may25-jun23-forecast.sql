-- Demo seed: analytics-ready equipment and two years of reservation demand.
-- Goal: visible, realistic demand lift from May 25 through Jun 23 with quieter months around it.
--
-- Run from the repo root while Docker is running:
--   Get-Content -Raw docs/demo-seeds/two-year-may25-jun23-forecast.sql | docker compose -f compose.dev.yml exec -T database psql -U techreserve_user -d techreserve
--
-- Remove this data with:
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

WITH equipment_seed AS (
    SELECT *
    FROM (
        VALUES
            ('Canon EOS R50', 'Camera', 6, 4, 'Available', 'Available', 'Mirrorless camera kit for photo and video reservations.', 'Canon', '90001', 'TR-CAM-001', 'TR-CAM-001'),
            ('Tripod Pro', 'Support', 10, 8, 'Available', 'Available', 'Heavy-duty tripod for cameras and projectors.', 'Manfrotto', '90002', 'TR-SUP-001', 'TR-SUP-001'),
            ('Sony A7 IV', 'Camera', 4, 2, 'Available', 'Available', 'Full-frame camera kit for high-demand coverage.', 'Sony', '90003', 'TR-CAM-002', 'TR-CAM-002'),
            ('Wireless Mic Kit', 'Audio', 12, 8, 'Available', 'Available', 'Dual-channel wireless microphone kit.', 'Rode', '90004', 'TR-AUD-001', 'TR-AUD-001'),
            ('LED Panel Light', 'Lighting', 6, 1, 'Under Maintenance', 'Under Maintenance', 'Portable light panel with two units currently being serviced.', 'Aputure', '90005', 'TR-LGT-001', 'TR-LGT-001'),
            ('Projector X200', 'Display', 3, 0, 'Unavailable', 'Unavailable', 'Projector set held back while replacement lamps are checked.', 'Epson', '90006', 'TR-DSP-001', 'TR-DSP-001'),
            ('Extension Cord 20m', 'Accessories', 15, 13, 'Available', 'Available', 'Long extension cord for rooms and event spaces.', 'Panther', '90007', 'TR-ACC-001', 'TR-ACC-001'),
            ('PA Speaker Set', 'Audio', 5, 3, 'Available', 'Available', 'Portable speaker pair for orientations and seminars.', 'JBL', '91001', 'DEM-AUD-001', 'DEM-AUD-001'),
            ('Laptop Cart', 'Computing', 2, 1, 'Under Maintenance', 'Under Maintenance', 'Mobile laptop cart with one unit in battery service.', 'Lenovo', '91002', 'DEM-CMP-001', 'DEM-CMP-001')
    ) AS seed(equipment_name, equipment_category, total_quantity, available_quantity, operational_status, equipment_state, description, equipment_brand, barcode, asset_id, serial_number)
),
backup_existing AS (
    INSERT INTO demo_seed_equipment_backup (
        equipment_name,
        equipment_category,
        total_quantity,
        available_quantity,
        operational_status,
        equipment_state,
        description,
        equipment_brand,
        barcode,
        asset_id,
        serial_number,
        updated_at
    )
    SELECT
        equipment.equipment_name,
        equipment.equipment_category,
        equipment.total_quantity,
        equipment.available_quantity,
        equipment.operational_status,
        equipment.equipment_state,
        equipment.description,
        equipment.equipment_brand,
        equipment.barcode,
        equipment.asset_id,
        equipment.serial_number,
        equipment.updated_at
    FROM equipment
    JOIN equipment_seed ON equipment_seed.equipment_name = equipment.equipment_name
    ON CONFLICT (equipment_name) DO NOTHING
    RETURNING equipment_name
),
updated_existing AS (
    UPDATE equipment
    SET
        equipment_category = equipment_seed.equipment_category,
        total_quantity = equipment_seed.total_quantity,
        available_quantity = equipment_seed.available_quantity,
        operational_status = equipment_seed.operational_status,
        equipment_state = equipment_seed.equipment_state,
        description = equipment_seed.description,
        equipment_brand = equipment_seed.equipment_brand,
        updated_at = NOW()
    FROM equipment_seed
    WHERE equipment.equipment_name = equipment_seed.equipment_name
    RETURNING equipment.equipment_name
)
INSERT INTO equipment (
    equipment_name,
    equipment_category,
    total_quantity,
    available_quantity,
    operational_status,
    created_at,
    updated_at,
    equipment_state,
    description,
    equipment_brand,
    barcode,
    asset_id,
    serial_number
)
SELECT
    equipment_seed.equipment_name,
    equipment_seed.equipment_category,
    equipment_seed.total_quantity,
    equipment_seed.available_quantity,
    equipment_seed.operational_status,
    NOW(),
    NOW(),
    equipment_seed.equipment_state,
    equipment_seed.description,
    equipment_seed.equipment_brand,
    equipment_seed.barcode,
    equipment_seed.asset_id,
    equipment_seed.serial_number
FROM equipment_seed
WHERE NOT EXISTS (
    SELECT 1
    FROM equipment
    WHERE equipment.equipment_name = equipment_seed.equipment_name
);

INSERT INTO accounts (
    last_name,
    first_name,
    email_address,
    role_designation,
    contact_number,
    is_active,
    created_timestamp,
    updated_timestamp,
    status,
    is_approved,
    is_verified,
    verification_status,
    invitation_status,
    username,
    id_number,
    department,
    invited_at,
    approved_at
)
VALUES
    ('Borrower', 'Analytics Demo', 'analytics.demo.borrower@techreserve.local', 'ROLE_BORROWER', '09170000001', TRUE, NOW(), NOW(), 'approved', TRUE, TRUE, 'verified', 'accepted', 'analyticsdemo', 'DEMO-BOR-001', 'Enrollment Office', NOW() - INTERVAL '2 years', NOW() - INTERVAL '2 years'),
    ('Borrower', 'Research Demo', 'research.demo.borrower@techreserve.local', 'ROLE_BORROWER', '09170000002', TRUE, NOW(), NOW(), 'approved', TRUE, TRUE, 'verified', 'accepted', 'researchdemo', 'DEMO-BOR-002', 'Academic Affairs', NOW() - INTERVAL '2 years', NOW() - INTERVAL '2 years')
ON CONFLICT (email_address) DO UPDATE
SET
    status = 'approved',
    is_approved = TRUE,
    is_verified = TRUE,
    verification_status = 'verified',
    invitation_status = 'accepted',
    updated_timestamp = NOW();

WITH borrower_pool AS (
    SELECT
        account_identifier,
        ROW_NUMBER() OVER (ORDER BY email_address) AS borrower_index
    FROM accounts
    WHERE email_address IN (
        'analytics.demo.borrower@techreserve.local',
        'research.demo.borrower@techreserve.local'
    )
),
reservation_seed AS (
    SELECT *
    FROM (
        VALUES
            ('2024-05-25'::date, 'Enrollment media kit kickoff', 5, 'Approved', 'Enrollment Office', 1, 'High'),
            ('2024-05-29'::date, 'Incoming student briefing', 6, 'Completed', 'Enrollment Office', 2, 'Normal'),
            ('2024-06-02'::date, 'Orientation livestream setup', 7, 'Approved', 'Enrollment Office', 1, 'High'),
            ('2024-06-06'::date, 'Faculty onboarding workshop', 5, 'Completed', 'Academic Affairs', 2, 'Normal'),
            ('2024-06-10'::date, 'Department training week', 6, 'Approved', 'Academic Affairs', 1, 'Normal'),
            ('2024-06-14'::date, 'Registrar service desk support', 4, 'Rejected', 'Registrar', 2, 'Low'),
            ('2024-06-18'::date, 'Mini conference support', 7, 'Completed', 'Enrollment Office', 1, 'High'),
            ('2024-06-23'::date, 'Closing week equipment demand', 6, 'Approved', 'Enrollment Office', 2, 'Normal'),
            ('2024-07-16'::date, 'Low-volume lab documentation', 2, 'Completed', 'TechReserve Demo Org', 1, 'Low'),
            ('2024-08-20'::date, 'Quiet month seminar', 2, 'Approved', 'TechReserve Demo Org', 2, 'Low'),
            ('2024-09-11'::date, 'Opening term showcase', 3, 'Completed', 'Academic Affairs', 1, 'Normal'),
            ('2024-10-17'::date, 'Department demo day', 3, 'Approved', 'Academic Affairs', 2, 'Normal'),
            ('2024-11-12'::date, 'Research poster week', 2, 'Rejected', 'Research Office', 1, 'Low'),
            ('2024-12-05'::date, 'Year-end event prep', 3, 'Completed', 'TechReserve Demo Org', 2, 'Normal'),
            ('2025-01-17'::date, 'New term orientation', 3, 'Approved', 'Enrollment Office', 1, 'Normal'),
            ('2025-02-13'::date, 'Workshop season support', 2, 'Completed', 'Academic Affairs', 2, 'Low'),
            ('2025-03-07'::date, 'Capstone practice session', 3, 'Approved', 'Research Office', 1, 'Normal'),
            ('2025-04-15'::date, 'Pre-enrollment activities', 3, 'Completed', 'Enrollment Office', 2, 'Normal'),
            ('2025-05-25'::date, 'Enrollment media kit kickoff', 6, 'Approved', 'Enrollment Office', 1, 'High'),
            ('2025-05-28'::date, 'Admission interview support', 5, 'Completed', 'Enrollment Office', 2, 'Normal'),
            ('2025-06-01'::date, 'Orientation livestream setup', 8, 'Approved', 'Enrollment Office', 1, 'High'),
            ('2025-06-04'::date, 'Welcome week briefing', 7, 'Completed', 'Enrollment Office', 2, 'High'),
            ('2025-06-08'::date, 'Faculty onboarding workshop', 6, 'Approved', 'Academic Affairs', 1, 'Normal'),
            ('2025-06-11'::date, 'Department training week', 7, 'Completed', 'Academic Affairs', 2, 'High'),
            ('2025-06-15'::date, 'Registrar service desk support', 4, 'Rejected', 'Registrar', 1, 'Low'),
            ('2025-06-18'::date, 'Mini conference support', 8, 'Approved', 'Enrollment Office', 2, 'High'),
            ('2025-06-21'::date, 'Student org orientation', 6, 'Completed', 'Student Affairs', 1, 'Normal'),
            ('2025-06-23'::date, 'Closing week equipment demand', 7, 'Approved', 'Enrollment Office', 2, 'High'),
            ('2025-07-15'::date, 'Low-volume lab documentation', 2, 'Completed', 'TechReserve Demo Org', 1, 'Low'),
            ('2025-08-19'::date, 'Quiet month seminar', 2, 'Approved', 'TechReserve Demo Org', 2, 'Low'),
            ('2025-09-10'::date, 'Opening term showcase', 4, 'Completed', 'Academic Affairs', 1, 'Normal'),
            ('2025-10-16'::date, 'Department demo day', 3, 'Approved', 'Academic Affairs', 2, 'Normal'),
            ('2025-11-11'::date, 'Research poster week', 2, 'Completed', 'Research Office', 1, 'Low'),
            ('2025-12-04'::date, 'Year-end event prep', 3, 'Approved', 'TechReserve Demo Org', 2, 'Normal'),
            ('2026-01-16'::date, 'New term orientation', 3, 'Completed', 'Enrollment Office', 1, 'Normal'),
            ('2026-02-12'::date, 'Workshop season support', 2, 'Approved', 'Academic Affairs', 2, 'Low'),
            ('2026-03-06'::date, 'Capstone practice session', 3, 'Rejected', 'Research Office', 1, 'Low'),
            ('2026-04-14'::date, 'Pre-enrollment activities', 3, 'Completed', 'Enrollment Office', 2, 'Normal'),
            ('2026-05-25'::date, 'Enrollment media kit kickoff', 6, 'Approved', 'Enrollment Office', 1, 'High'),
            ('2026-05-27'::date, 'Admission interview support', 5, 'Completed', 'Enrollment Office', 2, 'Normal'),
            ('2026-05-30'::date, 'Campus tour documentation', 6, 'Approved', 'Enrollment Office', 1, 'Normal'),
            ('2026-06-02'::date, 'Orientation livestream setup', 8, 'Approved', 'Enrollment Office', 2, 'High'),
            ('2026-06-05'::date, 'Welcome week briefing', 7, 'Completed', 'Enrollment Office', 1, 'High'),
            ('2026-06-08'::date, 'Faculty onboarding workshop', 6, 'Approved', 'Academic Affairs', 2, 'Normal'),
            ('2026-06-11'::date, 'Department training week', 7, 'Pending Review', 'Academic Affairs', 1, 'High'),
            ('2026-06-14'::date, 'Registrar service desk support', 5, 'Pending Review', 'Registrar', 2, 'Normal'),
            ('2026-06-17'::date, 'Mini conference support', 8, 'Approved', 'Enrollment Office', 1, 'High'),
            ('2026-06-19'::date, 'Student org orientation', 6, 'Completed', 'Student Affairs', 2, 'Normal'),
            ('2026-06-21'::date, 'Enrollment overflow request', 5, 'Pending Review', 'Enrollment Office', 1, 'Normal'),
            ('2026-06-23'::date, 'Closing week equipment demand', 7, 'Approved', 'Enrollment Office', 2, 'High')
    ) AS seed(submission_date, purpose_description, requested_quantity, current_status, organization_name, borrower_index, priority_level)
)
INSERT INTO reservations (
    reservation_code,
    borrower_account_id,
    organization_name,
    venue_identifier,
    requested_equipment_list,
    requested_quantity,
    event_date_time,
    purpose_description,
    activity_type,
    current_status,
    priority_level,
    rejection_reason,
    supporting_documents,
    submission_timestamp,
    updated_timestamp,
    end_date_time
)
SELECT
    'DEMO-' || TO_CHAR(reservation_seed.submission_date, 'YYYYMMDD') || '-' || LPAD(ROW_NUMBER() OVER (ORDER BY reservation_seed.submission_date)::text, 2, '0'),
    borrower_pool.account_identifier,
    reservation_seed.organization_name,
    NULL,
    CASE
        WHEN EXTRACT(MONTH FROM reservation_seed.submission_date) IN (5, 6) THEN
            '[{"equipmentName":"Canon EOS R50","quantity":2},{"equipmentName":"Wireless Mic Kit","quantity":2},{"equipmentName":"Tripod Pro","quantity":1},{"equipmentName":"PA Speaker Set","quantity":1}]'::json
        WHEN reservation_seed.organization_name = 'Academic Affairs' THEN
            '[{"equipmentName":"Sony A7 IV","quantity":1},{"equipmentName":"LED Panel Light","quantity":2},{"equipmentName":"Laptop Cart","quantity":1}]'::json
        WHEN reservation_seed.organization_name = 'Research Office' THEN
            '[{"equipmentName":"Sony A7 IV","quantity":1},{"equipmentName":"Tripod Pro","quantity":1}]'::json
        ELSE
            '[{"equipmentName":"Extension Cord 20m","quantity":1},{"equipmentName":"Projector X200","quantity":1},{"equipmentName":"Tripod Pro","quantity":1}]'::json
    END,
    reservation_seed.requested_quantity,
    reservation_seed.submission_date::timestamp + INTERVAL '10 days' + INTERVAL '9 hours',
    reservation_seed.purpose_description,
    CASE
        WHEN reservation_seed.current_status = 'Pending Review' THEN 'Workshop'
        WHEN reservation_seed.current_status = 'Rejected' THEN 'Administrative'
        WHEN EXTRACT(MONTH FROM reservation_seed.submission_date) IN (5, 6) THEN 'Orientation'
        ELSE 'Event'
    END,
    reservation_seed.current_status,
    reservation_seed.priority_level,
    CASE
        WHEN reservation_seed.current_status = 'Rejected' THEN 'Requested quantity exceeded practical allocation for that date.'
        ELSE NULL
    END,
    '[]'::json,
    reservation_seed.submission_date::timestamp + INTERVAL '8 hours',
    NOW(),
    reservation_seed.submission_date::timestamp + INTERVAL '10 days' + INTERVAL '13 hours'
FROM reservation_seed
JOIN borrower_pool ON borrower_pool.borrower_index = reservation_seed.borrower_index
ON CONFLICT (reservation_code) DO NOTHING;

COMMIT;
