-- Demo seed: analytics-ready equipment and a two-period quiet-baseline forecast.
-- Dataset: exactly 300 idempotent demo reservations from 2025-05-25 through 2026-12-23.
-- Narrative: low demand last year and low demand this semester.
-- Demand profile:
--   * 2025-05-25 to 2025-06-23:  15 reservations (prior-year low-demand comparison window)
--   * 2025-06-24 to 2025-08-31:  37 reservations (quiet baseline)
--   * 2025-09-01 to 2025-12-31:  65 reservations (steady baseline)
--   * 2026-01-01 to 2026-05-24:  82 reservations (steady baseline)
--   * 2026-05-25 to 2026-06-23:  15 reservations (current-term low-demand comparison window)
--   * 2026-06-24 to 2026-08-31:  37 reservations (quiet baseline)
--   * 2026-09-01 to 2026-12-23:  49 reservations (quiet year-end baseline)
--
-- Scenario behavior: DEMO-2526-001 through DEMO-2526-300 are updated in place.
-- This replaces the other May/June demand scenarios without accumulating duplicate demo rows.
--
-- Run from the repo root while Docker is running:
--   Get-Content -Raw docs/demo-seeds/LowToLow.sql | docker compose -f compose.dev.yml exec -T database psql -U techreserve_user -d techreserve
--
-- Rerunning this script updates its DEMO-2526-* reservations instead of adding duplicates.

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
            ('Laptop Cart', 'Computing', 2, 1, 'Under Maintenance', 'Under Maintenance', 'Mobile laptop cart with one unit in battery service.', 'Lenovo', '91002', 'DEM-CMP-001', 'DEM-CMP-001'),
            ('DSLR Kit', 'Camera', 4, 2, 'Available', 'Available', 'Backup DSLR set for events and quick shoots.', 'Nikon', '91003', 'DEM-CAM-003', 'DEM-CAM-003'),
            ('GoPro Action Kit', 'Camera', 3, 2, 'Available', 'Available', 'Action camera bundle for compact field coverage.', 'GoPro', '91004', 'DEM-CAM-004', 'DEM-CAM-004'),
            ('Podcast Mic Set', 'Audio', 6, 4, 'Available', 'Available', 'Desktop microphone set for recording and interviews.', 'Shure', '91005', 'DEM-AUD-002', 'DEM-AUD-002'),
            ('Portable Mixer', 'Audio', 3, 2, 'Available', 'Available', 'Compact audio mixer for small productions.', 'Yamaha', '91006', 'DEM-AUD-003', 'DEM-AUD-003'),
            ('Stage Light Bar', 'Lighting', 5, 3, 'Available', 'Available', 'Multi-angle light bar for stage and event use.', 'Nanlite', '91007', 'DEM-LGT-002', 'DEM-LGT-002'),
            ('Ring Light Pro', 'Lighting', 8, 6, 'Available', 'Available', 'Ring light for interviews and livestream support.', 'Neewer', '91008', 'DEM-LGT-003', 'DEM-LGT-003'),
            ('Projector Mini HD', 'Display', 4, 2, 'Available', 'Available', 'Portable projector for small rooms and demos.', 'BenQ', '91009', 'DEM-DSP-002', 'DEM-DSP-002'),
            ('Portable TV Stand', 'Display', 4, 3, 'Available', 'Available', 'Mobile stand for display screens and presentations.', 'AVF', '91010', 'DEM-DSP-003', 'DEM-DSP-003'),
            ('HDMI Switcher', 'Accessories', 6, 5, 'Available', 'Available', 'Input switcher for mixed display sources.', 'Ugreen', '91011', 'DEM-ACC-002', 'DEM-ACC-002'),
            ('Cable Kit Pro', 'Accessories', 12, 10, 'Available', 'Available', 'Assorted adapters and spare cables.', 'Anker', '91012', 'DEM-ACC-003', 'DEM-ACC-003'),
            ('Speaker Stand', 'Support', 8, 7, 'Available', 'Available', 'Adjustable stand for audio equipment.', 'K&M', '91013', 'DEM-SUP-002', 'DEM-SUP-002'),
            ('Backdrop Stand', 'Support', 5, 4, 'Available', 'Available', 'Frame stand for backdrops and banners.', 'Selens', '91014', 'DEM-SUP-003', 'DEM-SUP-003'),
            ('Tablet Cart', 'Computing', 3, 2, 'Available', 'Available', 'Charging cart for tablets and mobile devices.', 'Dell', '91015', 'DEM-CMP-002', 'DEM-CMP-002'),
            ('Wireless Presenter', 'Computing', 10, 9, 'Available', 'Available', 'Presenter clicker for lectures and briefings.', 'Logitech', '91016', 'DEM-CMP-003', 'DEM-CMP-003'),
            ('Battery Pack 20k', 'Accessories', 20, 18, 'Available', 'Available', 'Power bank set for field coverage.', 'Baseus', '91017', 'DEM-ACC-004', 'DEM-ACC-004'),
            ('Tripod Mini', 'Support', 12, 10, 'Available', 'Available', 'Small tripod for handheld camera stabilization.', 'Ulanzi', '91018', 'DEM-SUP-004', 'DEM-SUP-004'),
            ('Green Screen Roll', 'Accessories', 4, 3, 'Available', 'Available', 'Backdrop roll for recordings and livestreams.', 'Elgato', '91019', 'DEM-ACC-005', 'DEM-ACC-005'),
            ('LED Tube Light', 'Lighting', 6, 4, 'Available', 'Available', 'Tube light for creative lighting setups.', 'Aputure', '91020', 'DEM-LGT-004', 'DEM-LGT-004'),
            ('Field Recorder', 'Audio', 4, 2, 'Available', 'Available', 'Portable recorder for on-site interviews.', 'Zoom', '91021', 'DEM-AUD-004', 'DEM-AUD-004'),
            ('Ceiling Projector', 'Display', 2, 1, 'Under Maintenance', 'Under Maintenance', 'Installed projector awaiting lamp replacement.', 'Epson', '91022', 'DEM-DSP-004', 'DEM-DSP-004'),
            ('Document Scanner', 'Computing', 3, 2, 'Available', 'Available', 'Fast scanner for registration and archives.', 'Canon', '91023', 'DEM-CMP-004', 'DEM-CMP-004'),
            ('Extension Cord 50m', 'Accessories', 8, 6, 'Available', 'Available', 'Long-range power cable for larger venues.', 'Panther', '91024', 'DEM-ACC-006', 'DEM-ACC-006'),
            ('Lighting Softbox', 'Lighting', 5, 3, 'Available', 'Available', 'Softbox kit for interviews and portraits.', 'Godox', '91025', 'DEM-LGT-005', 'DEM-LGT-005'),
            ('PA Subwoofer', 'Audio', 2, 1, 'Available', 'Available', 'Low-end audio support for large events.', 'JBL', '91026', 'DEM-AUD-005', 'DEM-AUD-005'),
            ('Teleprompter Kit', 'Computing', 2, 1, 'Available', 'Available', 'Teleprompter bundle for formal recordings.', 'Desview', '91027', 'DEM-CMP-005', 'DEM-CMP-005'),
            ('Camera Slider', 'Support', 3, 2, 'Available', 'Available', 'Smooth-motion slider for video capture.', 'Neewer', '91028', 'DEM-SUP-005', 'DEM-SUP-005'),
            ('Audio Interface', 'Audio', 5, 4, 'Available', 'Available', 'Interface for recording and livestream audio routing.', 'Focusrite', '91029', 'DEM-AUD-006', 'DEM-AUD-006'),
            ('Portable Monitor', 'Display', 6, 4, 'Available', 'Available', 'Secondary screen for production monitoring.', 'ASUS', '91030', 'DEM-DSP-005', 'DEM-DSP-005')
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
        barcode = equipment_seed.barcode,
        asset_id = equipment_seed.asset_id,
        serial_number = equipment_seed.serial_number,
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


-- Six controlled demo borrowers make borrower and department analytics look credible
-- without relying on production accounts.
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
    ('Borrower', 'Research Demo', 'research.demo.borrower@techreserve.local', 'ROLE_BORROWER', '09170000002', TRUE, NOW(), NOW(), 'approved', TRUE, TRUE, 'verified', 'accepted', 'researchdemo', 'DEMO-BOR-002', 'Research Office', NOW() - INTERVAL '2 years', NOW() - INTERVAL '2 years'),
    ('Borrower', 'Enrollment Demo', 'enrollment.demo.borrower@techreserve.local', 'ROLE_BORROWER', '09170000003', TRUE, NOW(), NOW(), 'approved', TRUE, TRUE, 'verified', 'accepted', 'enrollmentdemo', 'DEMO-BOR-003', 'Enrollment Office', NOW() - INTERVAL '2 years', NOW() - INTERVAL '2 years'),
    ('Borrower', 'Student Affairs Demo', 'studentaffairs.demo.borrower@techreserve.local', 'ROLE_BORROWER', '09170000004', TRUE, NOW(), NOW(), 'approved', TRUE, TRUE, 'verified', 'accepted', 'studentaffairsdemo', 'DEMO-BOR-004', 'Student Affairs', NOW() - INTERVAL '2 years', NOW() - INTERVAL '2 years'),
    ('Borrower', 'Academic Affairs Demo', 'academics.demo.borrower@techreserve.local', 'ROLE_BORROWER', '09170000005', TRUE, NOW(), NOW(), 'approved', TRUE, TRUE, 'verified', 'accepted', 'academicsdemo', 'DEMO-BOR-005', 'Academic Affairs', NOW() - INTERVAL '2 years', NOW() - INTERVAL '2 years'),
    ('Borrower', 'Registrar Demo', 'registrar.demo.borrower@techreserve.local', 'ROLE_BORROWER', '09170000006', TRUE, NOW(), NOW(), 'approved', TRUE, TRUE, 'verified', 'accepted', 'registrardemo', 'DEMO-BOR-006', 'Registrar', NOW() - INTERVAL '2 years', NOW() - INTERVAL '2 years')
ON CONFLICT (email_address) DO UPDATE
SET
    status = 'approved',
    is_active = TRUE,
    is_approved = TRUE,
    is_verified = TRUE,
    verification_status = 'verified',
    invitation_status = 'accepted',
    updated_timestamp = NOW();

WITH borrower_pool AS (
    SELECT
        account_identifier,
        CASE email_address
            WHEN 'analytics.demo.borrower@techreserve.local' THEN 1
            WHEN 'research.demo.borrower@techreserve.local' THEN 2
            WHEN 'enrollment.demo.borrower@techreserve.local' THEN 3
            WHEN 'studentaffairs.demo.borrower@techreserve.local' THEN 4
            WHEN 'academics.demo.borrower@techreserve.local' THEN 5
            WHEN 'registrar.demo.borrower@techreserve.local' THEN 6
        END AS borrower_slot
    FROM accounts
    WHERE email_address IN (
        'analytics.demo.borrower@techreserve.local',
        'research.demo.borrower@techreserve.local',
        'enrollment.demo.borrower@techreserve.local',
        'studentaffairs.demo.borrower@techreserve.local',
        'academics.demo.borrower@techreserve.local',
        'registrar.demo.borrower@techreserve.local'
    )
),
periods AS (
    SELECT *
    FROM (
        VALUES
            (1, DATE '2025-05-25', DATE '2025-06-23', 15, 'prior_low'),
            (2, DATE '2025-06-24', DATE '2025-08-31', 37, 'baseline'),
            (3, DATE '2025-09-01', DATE '2025-12-31', 65, 'baseline'),
            (4, DATE '2026-01-01', DATE '2026-05-24', 82, 'baseline'),
            (5, DATE '2026-05-25', DATE '2026-06-23', 15, 'current_low'),
            (6, DATE '2026-06-24', DATE '2026-08-31', 37, 'baseline'),
            (7, DATE '2026-09-01', DATE '2026-12-23', 49, 'baseline')
    ) AS period_data(period_id, start_date, end_date, reservation_count, demand_band)
),
business_days AS (
    SELECT
        periods.period_id,
        generated_day.calendar_day::date AS submission_date,
        ROW_NUMBER() OVER (
            PARTITION BY periods.period_id
            ORDER BY generated_day.calendar_day
        ) AS day_index
    FROM periods
    CROSS JOIN LATERAL generate_series(
        periods.start_date::timestamp,
        periods.end_date::timestamp,
        INTERVAL '1 day'
    ) AS generated_day(calendar_day)
    WHERE EXTRACT(ISODOW FROM generated_day.calendar_day) <= 6
),
business_day_counts AS (
    SELECT
        period_id,
        COUNT(*) AS day_count
    FROM business_days
    GROUP BY period_id
),
reservation_slots AS (
    SELECT
        periods.period_id,
        periods.demand_band,
        generated_slot.slot_no,
        1 + MOD(
            ((generated_slot.slot_no * 17) + (periods.period_id * 11))::bigint,
            business_day_counts.day_count
        ) AS day_index
    FROM periods
    JOIN business_day_counts
        ON business_day_counts.period_id = periods.period_id
    CROSS JOIN LATERAL generate_series(1, periods.reservation_count) AS generated_slot(slot_no)
),
scattered_reservations AS (
    SELECT
        reservation_slots.period_id,
        reservation_slots.demand_band,
        reservation_slots.slot_no,
        business_days.submission_date,
        ROW_NUMBER() OVER (
            ORDER BY
                business_days.submission_date,
                reservation_slots.period_id,
                reservation_slots.slot_no
        ) AS seed_sequence
    FROM reservation_slots
    JOIN business_days
        ON business_days.period_id = reservation_slots.period_id
        AND business_days.day_index = reservation_slots.day_index
),
kit_catalog AS (
    SELECT *
    FROM (
        VALUES
            (1, 'Enrollment Office', 3, 'Documentation', 'Light enrollment media documentation', '[{"equipmentName":"Canon EOS R50","quantity":1},{"equipmentName":"Tripod Pro","quantity":1}]'::json, 2),
            (2, 'Student Affairs', 4, 'Orientation', 'Small student orientation AV support', '[{"equipmentName":"PA Speaker Set","quantity":1},{"equipmentName":"Wireless Mic Kit","quantity":1},{"equipmentName":"Extension Cord 20m","quantity":1}]'::json, 3),
            (3, 'Academic Affairs', 5, 'Presentation', 'Compact faculty presentation support', '[{"equipmentName":"Projector Mini HD","quantity":1},{"equipmentName":"Wireless Presenter","quantity":1},{"equipmentName":"HDMI Switcher","quantity":1}]'::json, 3),
            (4, 'Research Office', 2, 'Documentation', 'Research documentation capture', '[{"equipmentName":"Sony A7 IV","quantity":1},{"equipmentName":"Tripod Pro","quantity":1},{"equipmentName":"Field Recorder","quantity":1}]'::json, 3),
            (5, 'Academic Affairs', 5, 'Workshop', 'Small workshop presentation support', '[{"equipmentName":"Podcast Mic Set","quantity":1},{"equipmentName":"Portable Mixer","quantity":1},{"equipmentName":"Projector Mini HD","quantity":1},{"equipmentName":"Extension Cord 20m","quantity":1}]'::json, 4),
            (6, 'Student Affairs', 4, 'Event', 'Student activity field coverage', '[{"equipmentName":"GoPro Action Kit","quantity":1},{"equipmentName":"Tripod Mini","quantity":1},{"equipmentName":"Battery Pack 20k","quantity":2}]'::json, 4),
            (7, 'Enrollment Office', 3, 'Presentation', 'Admissions display setup', '[{"equipmentName":"Portable TV Stand","quantity":1},{"equipmentName":"Projector Mini HD","quantity":1},{"equipmentName":"HDMI Switcher","quantity":1}]'::json, 3),
            (8, 'TechReserve Demo Org', 1, 'Livestream', 'Small institutional livestream setup', '[{"equipmentName":"DSLR Kit","quantity":1},{"equipmentName":"Audio Interface","quantity":1},{"equipmentName":"Podcast Mic Set","quantity":1}]'::json, 3),
            (9, 'Research Office', 2, 'Interview', 'Research interview recording', '[{"equipmentName":"DSLR Kit","quantity":1},{"equipmentName":"Field Recorder","quantity":1},{"equipmentName":"Ring Light Pro","quantity":1}]'::json, 3),
            (10, 'Registrar', 6, 'Administrative', 'Registration desk equipment support', '[{"equipmentName":"Document Scanner","quantity":1},{"equipmentName":"Wireless Presenter","quantity":1}]'::json, 2),
            (11, 'Student Affairs', 4, 'Event', 'Small campus lighting support', '[{"equipmentName":"Stage Light Bar","quantity":1},{"equipmentName":"Ring Light Pro","quantity":1}]'::json, 2),
            (12, 'Academic Affairs', 5, 'Presentation', 'Portable presentation support', '[{"equipmentName":"Projector Mini HD","quantity":1},{"equipmentName":"Cable Kit Pro","quantity":1},{"equipmentName":"Portable Monitor","quantity":1}]'::json, 3),
            (13, 'TechReserve Demo Org', 1, 'Documentation', 'Short-form activity documentation', '[{"equipmentName":"DSLR Kit","quantity":1},{"equipmentName":"Tripod Mini","quantity":1}]'::json, 2),
            (14, 'Student Affairs', 4, 'Event', 'Small event audio support', '[{"equipmentName":"PA Speaker Set","quantity":1},{"equipmentName":"Portable Mixer","quantity":1},{"equipmentName":"Wireless Mic Kit","quantity":1}]'::json, 3),
            (15, 'Enrollment Office', 3, 'Documentation', 'Enrollment backdrop documentation', '[{"equipmentName":"Canon EOS R50","quantity":1},{"equipmentName":"Backdrop Stand","quantity":1},{"equipmentName":"Ring Light Pro","quantity":1}]'::json, 3),
            (16, 'Registrar', 6, 'Administrative', 'Administrative room display support', '[{"equipmentName":"Extension Cord 50m","quantity":1},{"equipmentName":"HDMI Switcher","quantity":1},{"equipmentName":"Portable TV Stand","quantity":1}]'::json, 3)
    ) AS kit(
        kit_id,
        organization_name,
        borrower_slot,
        activity_type,
        purpose_template,
        requested_equipment_list,
        requested_quantity
    )
),
enriched_reservations AS (
    SELECT
        scattered_reservations.*,
        kit_catalog.organization_name,
        kit_catalog.borrower_slot,
        kit_catalog.activity_type,
        kit_catalog.purpose_template,
        kit_catalog.requested_equipment_list,
        kit_catalog.requested_quantity
    FROM scattered_reservations
    JOIN kit_catalog
        ON kit_catalog.kit_id = 1 + MOD(scattered_reservations.seed_sequence * 11, 16)
),
statused_reservations AS (
    SELECT
        enriched_reservations.*,
        CASE
            WHEN submission_date > DATE '2026-06-25'
                AND MOD(seed_sequence, 7) = 0 THEN 'Pending Review'
            WHEN submission_date > DATE '2026-06-25' THEN 'Approved'
            WHEN MOD(seed_sequence, 23) = 0 THEN 'Rejected'
            WHEN MOD(seed_sequence, 5) = 0 THEN 'Approved'
            ELSE 'Completed'
        END AS current_status,
        CASE
            WHEN MOD(seed_sequence, 29) = 0 THEN 'High'
            WHEN requested_quantity <= 2 OR MOD(seed_sequence, 7) = 0 THEN 'Low'
            ELSE 'Normal'
        END AS priority_level,
        (
            submission_date::timestamp
            + make_interval(
                days => 3 + MOD(seed_sequence * 3, 11)::int,
                hours => 8 + MOD(seed_sequence * 5, 8)::int
            )
            + CASE
                WHEN MOD(seed_sequence, 2) = 0 THEN INTERVAL '0 minutes'
                ELSE INTERVAL '30 minutes'
            END
        ) AS event_date_time
    FROM enriched_reservations
),
final_reservations AS (
    SELECT
        'DEMO-2526-' || LPAD(seed_sequence::text, 3, '0') AS reservation_code,
        borrower_slot,
        organization_name,
        NULL::int AS venue_identifier,
        requested_equipment_list,
        requested_quantity,
        event_date_time,
        purpose_template || ' - ' || TO_CHAR(submission_date, 'Mon DD, YYYY') AS purpose_description,
        activity_type,
        current_status,
        priority_level,
        CASE
            WHEN current_status = 'Rejected'
                THEN 'Requested equipment was already allocated to a higher-priority activity.'
            ELSE NULL
        END AS rejection_reason,
        submission_date::timestamp
            + make_interval(hours => 7 + MOD(seed_sequence * 3, 10)::int) AS submission_timestamp,
        event_date_time
            + make_interval(hours => 2 + MOD(seed_sequence, 5)::int) AS end_date_time
    FROM statused_reservations
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
    final_reservations.reservation_code,
    borrower_pool.account_identifier,
    final_reservations.organization_name,
    final_reservations.venue_identifier,
    final_reservations.requested_equipment_list,
    final_reservations.requested_quantity,
    final_reservations.event_date_time,
    final_reservations.purpose_description,
    final_reservations.activity_type,
    final_reservations.current_status,
    final_reservations.priority_level,
    final_reservations.rejection_reason,
    '[]'::json,
    final_reservations.submission_timestamp,
    NOW(),
    final_reservations.end_date_time
FROM final_reservations
JOIN borrower_pool
    ON borrower_pool.borrower_slot = final_reservations.borrower_slot
ON CONFLICT (reservation_code) DO UPDATE
SET
    borrower_account_id = EXCLUDED.borrower_account_id,
    organization_name = EXCLUDED.organization_name,
    venue_identifier = EXCLUDED.venue_identifier,
    requested_equipment_list = EXCLUDED.requested_equipment_list,
    requested_quantity = EXCLUDED.requested_quantity,
    event_date_time = EXCLUDED.event_date_time,
    purpose_description = EXCLUDED.purpose_description,
    activity_type = EXCLUDED.activity_type,
    current_status = EXCLUDED.current_status,
    priority_level = EXCLUDED.priority_level,
    rejection_reason = EXCLUDED.rejection_reason,
    supporting_documents = EXCLUDED.supporting_documents,
    submission_timestamp = EXCLUDED.submission_timestamp,
    updated_timestamp = NOW(),
    end_date_time = EXCLUDED.end_date_time;

COMMIT;

-- Validation: this must report exactly 300 demo reservations, with 15 in each
-- May 25–June 23 comparison window and no seasonal high-demand spike.
SELECT
    COUNT(*) AS total_demo_reservations,
    COUNT(*) FILTER (
        WHERE submission_timestamp::date BETWEEN DATE '2025-05-25' AND DATE '2025-06-23'
    ) AS prior_year_low_demand,
    COUNT(*) FILTER (
        WHERE submission_timestamp::date BETWEEN DATE '2026-05-25' AND DATE '2026-06-23'
    ) AS current_term_low_demand,
    COUNT(*) FILTER (
        WHERE submission_timestamp::date BETWEEN DATE '2025-05-25' AND DATE '2026-12-23'
    ) AS in_requested_date_range
FROM reservations
WHERE reservation_code LIKE 'DEMO-2526-%';

SELECT
    TO_CHAR(submission_timestamp, 'YYYY-MM') AS submission_month,
    COUNT(*) AS reservations,
    SUM(requested_quantity) AS total_requested_units
FROM reservations
WHERE reservation_code LIKE 'DEMO-2526-%'
GROUP BY TO_CHAR(submission_timestamp, 'YYYY-MM')
ORDER BY submission_month;
