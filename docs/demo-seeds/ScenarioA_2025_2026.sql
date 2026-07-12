-- TechReserve BILP combined seed: Scenario A - 2025 and 2026.
-- Aim: validate balanced analytics with diverse activities, equipment, and staggered bookings.
-- Sources: ScenarioA_2025.sql and ScenarioA_2026.sql.
-- Dataset: exactly 40 idempotent reservations:
--   BILP-2025-A-001 through BILP-2025-A-020
--   BILP-2026-A-001 through BILP-2026-A-020
-- Rerunning this script updates matching reservation_code rows instead of adding duplicates.

BEGIN;

-- ============================================================
-- Scenario A - 2025 (20 reservations)
-- ============================================================
WITH reservation_seed AS (
    SELECT *
    FROM (
        VALUES
            ('BILP-2025-A-001', 301, NULL, NULL, '[{"equipmentIdentifier":75,"name":"Canon EOS R50","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 07:00:00', 'Academic Class', 'IT2201 Database Systems Class', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-08 05:58:00', TIMESTAMP '2025-07-08 05:58:00', TIMESTAMP '2025-07-12 07:45:00'),
            ('BILP-2025-A-002', 302, NULL, NULL, '[{"equipmentIdentifier":76,"name":"Tripod Pro","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 07:50:00', 'Thesis Defense', 'Smart Campus Monitoring System Thesis Defense', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-07 05:46:00', TIMESTAMP '2025-07-07 05:46:00', TIMESTAMP '2025-07-12 08:35:00'),
            ('BILP-2025-A-003', 303, NULL, NULL, '[{"equipmentIdentifier":78,"name":"Wireless Mic Kit","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 08:40:00', 'Seminar', 'Cybersecurity Awareness Seminar', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-06 05:34:00', TIMESTAMP '2025-07-06 05:34:00', TIMESTAMP '2025-07-12 09:25:00'),
            ('BILP-2025-A-004', 304, NULL, 18, '[{"equipmentIdentifier":81,"name":"Extension Cord 20m","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2025-07-12 09:30:00', 'Student Organization Event - Academic', 'Student Research Colloquium', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-05 05:22:00', TIMESTAMP '2025-07-05 05:22:00', TIMESTAMP '2025-07-12 10:15:00'),
            ('BILP-2025-A-005', 305, NULL, NULL, '[{"equipmentIdentifier":82,"name":"PA Speaker Set","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 10:20:00', 'Student Organization Event - Non-Academic', 'TechReserve Creatives Night', 'Completed', 'Low', NULL, '[]'::json, TIMESTAMP '2025-07-04 10:10:00', TIMESTAMP '2025-07-04 10:10:00', TIMESTAMP '2025-07-12 11:05:00'),
            ('BILP-2025-A-006', 306, NULL, NULL, '[{"equipmentIdentifier":84,"name":"DSLR Kit","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 11:10:00', 'Department Activity', 'College Research and Extension Planning', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-03 09:58:00', TIMESTAMP '2025-07-03 09:58:00', TIMESTAMP '2025-07-12 11:55:00'),
            ('BILP-2025-A-007', 307, NULL, NULL, '[{"equipmentIdentifier":85,"name":"GoPro Action Kit","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 12:00:00', 'Faculty Meeting', 'Midyear Faculty Coordination Meeting', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-09 09:46:00', TIMESTAMP '2025-07-09 09:46:00', TIMESTAMP '2025-07-12 12:45:00'),
            ('BILP-2025-A-008', 308, NULL, 41, '[{"equipmentIdentifier":86,"name":"Podcast Mic Set","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 12:50:00', 'Project/Capstone Presentation', 'Community Equipment Booking System Capstone Presentation', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-08 09:34:00', TIMESTAMP '2025-07-08 09:34:00', TIMESTAMP '2025-07-12 13:35:00'),
            ('BILP-2025-A-009', 309, NULL, NULL, '[{"equipmentIdentifier":87,"name":"Portable Mixer","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 13:40:00', 'Orientation Program', 'First-Year Information Technology Orientation', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-07 09:22:00', TIMESTAMP '2025-07-07 09:22:00', TIMESTAMP '2025-07-12 14:25:00'),
            ('BILP-2025-A-010', 310, NULL, NULL, '[{"equipmentIdentifier":88,"name":"Stage Light Bar","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 14:30:00', 'Performance/Production', 'Digital Media Showcase Production', 'Completed', 'Low', NULL, '[]'::json, TIMESTAMP '2025-07-06 14:10:00', TIMESTAMP '2025-07-06 14:10:00', TIMESTAMP '2025-07-12 15:15:00'),
            ('BILP-2025-A-011', 311, NULL, NULL, '[{"equipmentIdentifier":89,"name":"Ring Light Pro","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 15:20:00', 'General Assembly', 'Information Technology Student General Assembly', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-05 13:58:00', TIMESTAMP '2025-07-05 13:58:00', TIMESTAMP '2025-07-12 16:05:00'),
            ('BILP-2025-A-012', 312, NULL, 21, '[{"equipmentIdentifier":109,"name":"Camera Slider","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 16:10:00', 'Academic Class', 'IT3305 Human-Computer Interaction Class', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-04 13:46:00', TIMESTAMP '2025-07-04 13:46:00', TIMESTAMP '2025-07-12 16:55:00'),
            ('BILP-2025-A-013', 313, NULL, NULL, '[{"equipmentIdentifier":110,"name":"Audio Interface","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 07:10:00', 'Thesis Defense', 'AI-Assisted Learning Platform Thesis Defense', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-03 03:44:00', TIMESTAMP '2025-07-03 03:44:00', TIMESTAMP '2025-07-12 07:50:00'),
            ('BILP-2025-A-014', 314, NULL, NULL, '[{"equipmentIdentifier":111,"name":"Portable Monitor","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 08:05:00', 'Seminar', 'Responsible Artificial Intelligence Seminar', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-09 03:37:00', TIMESTAMP '2025-07-09 03:37:00', TIMESTAMP '2025-07-12 08:45:00'),
            ('BILP-2025-A-015', 315, NULL, NULL, '[{"equipmentIdentifier":90,"name":"Projector Mini HD","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 09:00:00', 'Department Activity', 'Department Curriculum Review Workshop', 'Completed', 'Low', NULL, '[]'::json, TIMESTAMP '2025-07-08 08:30:00', TIMESTAMP '2025-07-08 08:30:00', TIMESTAMP '2025-07-12 09:40:00'),
            ('BILP-2025-A-016', 316, NULL, 12, '[{"equipmentIdentifier":92,"name":"HDMI Switcher","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 10:00:00', 'Faculty Meeting', 'Faculty Research Mentoring Meeting', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-07 08:28:00', TIMESTAMP '2025-07-07 08:28:00', TIMESTAMP '2025-07-12 10:40:00'),
            ('BILP-2025-A-017', 317, NULL, NULL, '[{"equipmentIdentifier":97,"name":"Wireless Presenter","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 11:00:00', 'Project/Capstone Presentation', 'Campus Asset Tracking System Capstone Presentation', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-06 08:26:00', TIMESTAMP '2025-07-06 08:26:00', TIMESTAMP '2025-07-12 11:40:00'),
            ('BILP-2025-A-018', 318, NULL, NULL, '[{"equipmentIdentifier":102,"name":"Field Recorder","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 12:10:00', 'Orientation Program', 'Internship Pre-Deployment Orientation', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-05 08:34:00', TIMESTAMP '2025-07-05 08:34:00', TIMESTAMP '2025-07-12 12:50:00'),
            ('BILP-2025-A-019', 319, NULL, NULL, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 13:10:00', 'Performance/Production', 'Multimedia Arts Final Production', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-04 08:32:00', TIMESTAMP '2025-07-04 08:32:00', TIMESTAMP '2025-07-12 13:50:00'),
            ('BILP-2025-A-020', 320, NULL, 25, '[{"equipmentIdentifier":79,"name":"LED Panel Light","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 15:40:00', 'General Assembly', 'Student Leaders General Assembly', 'Completed', 'Low', NULL, '[]'::json, TIMESTAMP '2025-07-03 15:00:00', TIMESTAMP '2025-07-03 15:00:00', TIMESTAMP '2025-07-12 16:20:00')
    ) AS seed(
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
    reservation_code,
    (
        SELECT account_identifier
        FROM accounts
        WHERE is_active = TRUE
        ORDER BY
            CASE WHEN UPPER(role_designation) IN ('ROLE_BORROWER', 'BORROWER') THEN 0 ELSE 1 END,
            account_identifier
        LIMIT 1
    ) AS borrower_account_id,
    COALESCE(organization_name, 'Scenario A Analytics Demo') AS organization_name,
    CASE
        WHEN reservation_seed.venue_identifier IS NULL
          OR EXISTS (
              SELECT 1
              FROM venues
              WHERE venues.venue_identifier = reservation_seed.venue_identifier
          )
        THEN reservation_seed.venue_identifier
        ELSE NULL
    END AS venue_identifier,
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
FROM reservation_seed
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
    updated_timestamp = EXCLUDED.updated_timestamp,
    end_date_time = EXCLUDED.end_date_time;

-- ============================================================
-- Scenario A - 2026 (20 reservations)
-- ============================================================
WITH reservation_seed AS (
    SELECT *
    FROM (
        VALUES
            ('BILP-2026-A-001', 301, NULL, NULL, '[{"equipmentIdentifier":75,"name":"Canon EOS R50","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 07:00:00', 'Academic Class', 'IT2201 Database Systems Class', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-08 05:58:00', TIMESTAMP '2026-07-08 05:58:00', TIMESTAMP '2026-07-12 07:45:00'),
            ('BILP-2026-A-002', 302, NULL, NULL, '[{"equipmentIdentifier":76,"name":"Tripod Pro","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 07:50:00', 'Thesis Defense', 'Smart Campus Monitoring System Thesis Defense', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-07 05:46:00', TIMESTAMP '2026-07-07 05:46:00', TIMESTAMP '2026-07-12 08:35:00'),
            ('BILP-2026-A-003', 303, NULL, NULL, '[{"equipmentIdentifier":78,"name":"Wireless Mic Kit","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 08:40:00', 'Seminar', 'Cybersecurity Awareness Seminar', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-06 05:34:00', TIMESTAMP '2026-07-06 05:34:00', TIMESTAMP '2026-07-12 09:25:00'),
            ('BILP-2026-A-004', 304, NULL, 18, '[{"equipmentIdentifier":81,"name":"Extension Cord 20m","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2026-07-12 09:30:00', 'Student Organization Event - Academic', 'Student Research Colloquium', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-05 05:22:00', TIMESTAMP '2026-07-05 05:22:00', TIMESTAMP '2026-07-12 10:15:00'),
            ('BILP-2026-A-005', 305, NULL, NULL, '[{"equipmentIdentifier":82,"name":"PA Speaker Set","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 10:20:00', 'Student Organization Event - Non-Academic', 'TechReserve Creatives Night', 'Approved', 'Low', NULL, '[]'::json, TIMESTAMP '2026-07-04 10:10:00', TIMESTAMP '2026-07-04 10:10:00', TIMESTAMP '2026-07-12 11:05:00'),
            ('BILP-2026-A-006', 306, NULL, NULL, '[{"equipmentIdentifier":84,"name":"DSLR Kit","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 11:10:00', 'Department Activity', 'College Research and Extension Planning', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-03 09:58:00', TIMESTAMP '2026-07-03 09:58:00', TIMESTAMP '2026-07-12 11:55:00'),
            ('BILP-2026-A-007', 307, NULL, NULL, '[{"equipmentIdentifier":85,"name":"GoPro Action Kit","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 12:00:00', 'Faculty Meeting', 'Midyear Faculty Coordination Meeting', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-09 09:46:00', TIMESTAMP '2026-07-09 09:46:00', TIMESTAMP '2026-07-12 12:45:00'),
            ('BILP-2026-A-008', 308, NULL, 41, '[{"equipmentIdentifier":86,"name":"Podcast Mic Set","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 12:50:00', 'Project/Capstone Presentation', 'Community Equipment Booking System Capstone Presentation', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-08 09:34:00', TIMESTAMP '2026-07-08 09:34:00', TIMESTAMP '2026-07-12 13:35:00'),
            ('BILP-2026-A-009', 309, NULL, NULL, '[{"equipmentIdentifier":87,"name":"Portable Mixer","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 13:40:00', 'Orientation Program', 'First-Year Information Technology Orientation', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-07 09:22:00', TIMESTAMP '2026-07-07 09:22:00', TIMESTAMP '2026-07-12 14:25:00'),
            ('BILP-2026-A-010', 310, NULL, NULL, '[{"equipmentIdentifier":88,"name":"Stage Light Bar","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 14:30:00', 'Performance/Production', 'Digital Media Showcase Production', 'Approved', 'Low', NULL, '[]'::json, TIMESTAMP '2026-07-06 14:10:00', TIMESTAMP '2026-07-06 14:10:00', TIMESTAMP '2026-07-12 15:15:00'),
            ('BILP-2026-A-011', 311, NULL, NULL, '[{"equipmentIdentifier":89,"name":"Ring Light Pro","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 15:20:00', 'General Assembly', 'Information Technology Student General Assembly', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-05 13:58:00', TIMESTAMP '2026-07-05 13:58:00', TIMESTAMP '2026-07-12 16:05:00'),
            ('BILP-2026-A-012', 312, NULL, 21, '[{"equipmentIdentifier":109,"name":"Camera Slider","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 16:10:00', 'Academic Class', 'IT3305 Human-Computer Interaction Class', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-04 13:46:00', TIMESTAMP '2026-07-04 13:46:00', TIMESTAMP '2026-07-12 16:55:00'),
            ('BILP-2026-A-013', 313, NULL, NULL, '[{"equipmentIdentifier":110,"name":"Audio Interface","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 07:10:00', 'Thesis Defense', 'AI-Assisted Learning Platform Thesis Defense', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-03 03:44:00', TIMESTAMP '2026-07-03 03:44:00', TIMESTAMP '2026-07-12 07:50:00'),
            ('BILP-2026-A-014', 314, NULL, NULL, '[{"equipmentIdentifier":111,"name":"Portable Monitor","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 08:05:00', 'Seminar', 'Responsible Artificial Intelligence Seminar', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-09 03:37:00', TIMESTAMP '2026-07-09 03:37:00', TIMESTAMP '2026-07-12 08:45:00'),
            ('BILP-2026-A-015', 315, NULL, NULL, '[{"equipmentIdentifier":90,"name":"Projector Mini HD","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 09:00:00', 'Department Activity', 'Department Curriculum Review Workshop', 'Approved', 'Low', NULL, '[]'::json, TIMESTAMP '2026-07-08 08:30:00', TIMESTAMP '2026-07-08 08:30:00', TIMESTAMP '2026-07-12 09:40:00'),
            ('BILP-2026-A-016', 316, NULL, 12, '[{"equipmentIdentifier":92,"name":"HDMI Switcher","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 10:00:00', 'Faculty Meeting', 'Faculty Research Mentoring Meeting', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-07 08:28:00', TIMESTAMP '2026-07-07 08:28:00', TIMESTAMP '2026-07-12 10:40:00'),
            ('BILP-2026-A-017', 317, NULL, NULL, '[{"equipmentIdentifier":97,"name":"Wireless Presenter","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 11:00:00', 'Project/Capstone Presentation', 'Campus Asset Tracking System Capstone Presentation', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-06 08:26:00', TIMESTAMP '2026-07-06 08:26:00', TIMESTAMP '2026-07-12 11:40:00'),
            ('BILP-2026-A-018', 318, NULL, NULL, '[{"equipmentIdentifier":102,"name":"Field Recorder","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 12:10:00', 'Orientation Program', 'Internship Pre-Deployment Orientation', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-05 08:34:00', TIMESTAMP '2026-07-05 08:34:00', TIMESTAMP '2026-07-12 12:50:00'),
            ('BILP-2026-A-019', 319, NULL, NULL, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 13:10:00', 'Performance/Production', 'Multimedia Arts Final Production', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-04 08:32:00', TIMESTAMP '2026-07-04 08:32:00', TIMESTAMP '2026-07-12 13:50:00'),
            ('BILP-2026-A-020', 320, NULL, 25, '[{"equipmentIdentifier":79,"name":"LED Panel Light","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 15:40:00', 'General Assembly', 'Student Leaders General Assembly', 'Approved', 'Low', NULL, '[]'::json, TIMESTAMP '2026-07-03 15:00:00', TIMESTAMP '2026-07-03 15:00:00', TIMESTAMP '2026-07-12 16:20:00')
    ) AS seed(
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
    reservation_code,
    (
        SELECT account_identifier
        FROM accounts
        WHERE is_active = TRUE
        ORDER BY
            CASE WHEN UPPER(role_designation) IN ('ROLE_BORROWER', 'BORROWER') THEN 0 ELSE 1 END,
            account_identifier
        LIMIT 1
    ) AS borrower_account_id,
    COALESCE(organization_name, 'Scenario A Analytics Demo') AS organization_name,
    CASE
        WHEN reservation_seed.venue_identifier IS NULL
          OR EXISTS (
              SELECT 1
              FROM venues
              WHERE venues.venue_identifier = reservation_seed.venue_identifier
          )
        THEN reservation_seed.venue_identifier
        ELSE NULL
    END AS venue_identifier,
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
FROM reservation_seed
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
    updated_timestamp = EXCLUDED.updated_timestamp,
    end_date_time = EXCLUDED.end_date_time;

COMMIT;

-- Validation: should return 40 rows in total.
SELECT
    COUNT(*) AS seeded_reservations,
    MIN(submission_timestamp) AS earliest_submission,
    MAX(submission_timestamp) AS latest_submission
FROM reservations
WHERE reservation_code LIKE 'BILP-2025-A-%'
   OR reservation_code LIKE 'BILP-2026-A-%';

-- Validation by year, status, and priority.
SELECT
    SUBSTRING(reservation_code FROM 'BILP-([0-9]{4})-A-') AS scenario_year,
    current_status,
    priority_level,
    COUNT(*) AS reservations
FROM reservations
WHERE reservation_code LIKE 'BILP-2025-A-%'
   OR reservation_code LIKE 'BILP-2026-A-%'
GROUP BY scenario_year, current_status, priority_level
ORDER BY scenario_year, current_status, priority_level;
