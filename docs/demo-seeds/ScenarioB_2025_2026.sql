-- TechReserve BILP combined seed: Scenario B - 2025 and 2026.
-- Aim: expose equipment bottlenecks and readiness risk with concentrated, overlapping exam demand.
-- Sources: ScenarioB_2025.sql and ScenarioB_2026.sql.
-- Dataset: exactly 40 idempotent reservations:
--   BILP-2025-B-001 through BILP-2025-B-020
--   BILP-2026-B-001 through BILP-2026-B-020
-- Rerunning this script updates matching reservation_code rows instead of adding duplicates.

BEGIN;

-- ============================================================
-- Scenario B - 2025 (20 reservations)
-- ============================================================

WITH reservation_seed AS (
    SELECT *
    FROM (
        VALUES
            ('BILP-2025-B-001', 301, NULL, NULL, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 10:00:00', 'Examination / Assessment', 'IT1101 Departmental Examination', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-08 08:58:00', TIMESTAMP '2025-07-08 08:58:00', TIMESTAMP '2025-07-12 12:00:00'),
            ('BILP-2025-B-002', 302, NULL, NULL, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 10:00:00', 'Examination / Assessment', 'IT1102 Departmental Examination', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-07 07:56:00', TIMESTAMP '2025-07-07 07:56:00', TIMESTAMP '2025-07-12 12:00:00'),
            ('BILP-2025-B-003', 303, NULL, NULL, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 10:00:00', 'Examination / Assessment', 'IT2201 Departmental Examination', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-06 06:54:00', TIMESTAMP '2025-07-06 06:54:00', TIMESTAMP '2025-07-12 12:00:00'),
            ('BILP-2025-B-004', 304, NULL, 18, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 10:00:00', 'Examination / Assessment', 'IT2202 Departmental Examination', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-05 05:52:00', TIMESTAMP '2025-07-05 05:52:00', TIMESTAMP '2025-07-12 12:00:00'),
            ('BILP-2025-B-005', 305, NULL, NULL, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 10:00:00', 'Examination / Assessment', 'IT2203 Departmental Examination', 'Completed', 'Low', NULL, '[]'::json, TIMESTAMP '2025-07-04 09:50:00', TIMESTAMP '2025-07-04 09:50:00', TIMESTAMP '2025-07-12 12:00:00'),
            ('BILP-2025-B-006', 306, NULL, NULL, '[{"equipmentIdentifier":78,"name":"Wireless Mic Kit","selectedQuantity":3,"quantity":3}]'::json, 3, TIMESTAMP '2025-07-12 09:30:00', 'Examination / Assessment', 'CCS1101 Departmental Examination', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-03 08:18:00', TIMESTAMP '2025-07-03 08:18:00', TIMESTAMP '2025-07-12 11:30:00'),
            ('BILP-2025-B-007', 307, NULL, NULL, '[{"equipmentIdentifier":78,"name":"Wireless Mic Kit","selectedQuantity":3,"quantity":3}]'::json, 3, TIMESTAMP '2025-07-12 10:00:00', 'Examination / Assessment', 'CCS2201 Departmental Examination', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-09 07:46:00', TIMESTAMP '2025-07-09 07:46:00', TIMESTAMP '2025-07-12 12:00:00'),
            ('BILP-2025-B-008', 308, NULL, 41, '[{"equipmentIdentifier":78,"name":"Wireless Mic Kit","selectedQuantity":3,"quantity":3}]'::json, 3, TIMESTAMP '2025-07-12 10:30:00', 'Examination / Assessment', 'CCS2202 Departmental Examination', 'Completed', 'Low', NULL, '[]'::json, TIMESTAMP '2025-07-08 07:14:00', TIMESTAMP '2025-07-08 07:14:00', TIMESTAMP '2025-07-12 12:30:00'),
            ('BILP-2025-B-009', 309, NULL, NULL, '[{"equipmentIdentifier":75,"name":"Canon EOS R50","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2025-07-12 09:00:00', 'Examination / Assessment', 'IT1201 Departmental Examination', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-07 04:42:00', TIMESTAMP '2025-07-07 04:42:00', TIMESTAMP '2025-07-12 11:00:00'),
            ('BILP-2025-B-010', 310, NULL, NULL, '[{"equipmentIdentifier":75,"name":"Canon EOS R50","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2025-07-12 09:30:00', 'Examination / Assessment', 'IT2301 Departmental Examination', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-06 09:10:00', TIMESTAMP '2025-07-06 09:10:00', TIMESTAMP '2025-07-12 11:30:00'),
            ('BILP-2025-B-011', 311, NULL, NULL, '[{"equipmentIdentifier":75,"name":"Canon EOS R50","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2025-07-12 10:00:00', 'Examination / Assessment', 'IT2302 Departmental Examination', 'Completed', 'Low', NULL, '[]'::json, TIMESTAMP '2025-07-05 08:38:00', TIMESTAMP '2025-07-05 08:38:00', TIMESTAMP '2025-07-12 12:00:00'),
            ('BILP-2025-B-012', 312, NULL, 21, '[{"equipmentIdentifier":82,"name":"PA Speaker Set","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2025-07-12 13:00:00', 'Examination / Assessment', 'IT3301 Departmental Examination', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-04 10:36:00', TIMESTAMP '2025-07-04 10:36:00', TIMESTAMP '2025-07-12 15:00:00'),
            ('BILP-2025-B-013', 313, NULL, NULL, '[{"equipmentIdentifier":82,"name":"PA Speaker Set","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2025-07-12 13:30:00', 'Examination / Assessment', 'IT3302 Departmental Examination', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-03 10:04:00', TIMESTAMP '2025-07-03 10:04:00', TIMESTAMP '2025-07-12 15:30:00'),
            ('BILP-2025-B-014', 314, NULL, NULL, '[{"equipmentIdentifier":82,"name":"PA Speaker Set","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2025-07-12 14:00:00', 'Examination / Assessment', 'IT3303 Departmental Examination', 'Completed', 'Low', NULL, '[]'::json, TIMESTAMP '2025-07-09 09:32:00', TIMESTAMP '2025-07-09 09:32:00', TIMESTAMP '2025-07-12 16:00:00'),
            ('BILP-2025-B-015', 315, NULL, NULL, '[{"equipmentIdentifier":79,"name":"LED Panel Light","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 10:00:00', 'Examination / Assessment', 'CCS3301 Departmental Examination', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-08 09:30:00', TIMESTAMP '2025-07-08 09:30:00', TIMESTAMP '2025-07-12 12:00:00'),
            ('BILP-2025-B-016', 316, NULL, 12, '[{"equipmentIdentifier":79,"name":"LED Panel Light","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 10:30:00', 'Examination / Assessment', 'CCS3302 Departmental Examination', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-07 08:58:00', TIMESTAMP '2025-07-07 08:58:00', TIMESTAMP '2025-07-12 12:30:00'),
            ('BILP-2025-B-017', 317, NULL, NULL, '[{"equipmentIdentifier":79,"name":"LED Panel Light","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2025-07-12 11:00:00', 'Examination / Assessment', 'CCS3401 Departmental Examination', 'Completed', 'Low', NULL, '[]'::json, TIMESTAMP '2025-07-06 08:26:00', TIMESTAMP '2025-07-06 08:26:00', TIMESTAMP '2025-07-12 13:00:00'),
            ('BILP-2025-B-018', 318, NULL, NULL, '[{"equipmentIdentifier":111,"name":"Portable Monitor","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2025-07-12 15:00:00', 'Examination / Assessment', 'CCS3402 Departmental Examination', 'Completed', 'High', NULL, '[]'::json, TIMESTAMP '2025-07-05 11:24:00', TIMESTAMP '2025-07-05 11:24:00', TIMESTAMP '2025-07-12 17:00:00'),
            ('BILP-2025-B-019', 319, NULL, NULL, '[{"equipmentIdentifier":111,"name":"Portable Monitor","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2025-07-12 15:30:00', 'Examination / Assessment', 'IT4401 Departmental Examination', 'Completed', 'Normal', NULL, '[]'::json, TIMESTAMP '2025-07-04 10:52:00', TIMESTAMP '2025-07-04 10:52:00', TIMESTAMP '2025-07-12 17:30:00'),
            ('BILP-2025-B-020', 320, NULL, 25, '[{"equipmentIdentifier":111,"name":"Portable Monitor","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2025-07-12 16:00:00', 'Examination / Assessment', 'IT4402 Departmental Examination', 'Completed', 'Low', NULL, '[]'::json, TIMESTAMP '2025-07-03 15:20:00', TIMESTAMP '2025-07-03 15:20:00', TIMESTAMP '2025-07-12 18:00:00')
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
    COALESCE(organization_name, 'Scenario B Analytics Demo') AS organization_name,
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
-- Scenario B - 2026 (20 reservations)
-- ============================================================

WITH reservation_seed AS (
    SELECT *
    FROM (
        VALUES
            ('BILP-2026-B-001', 301, NULL, NULL, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 10:00:00', 'Examination / Assessment', 'IT1101 Departmental Examination', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-08 08:58:00', TIMESTAMP '2026-07-08 08:58:00', TIMESTAMP '2026-07-12 12:00:00'),
            ('BILP-2026-B-002', 302, NULL, NULL, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 10:00:00', 'Examination / Assessment', 'IT1102 Departmental Examination', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-07 07:56:00', TIMESTAMP '2026-07-07 07:56:00', TIMESTAMP '2026-07-12 12:00:00'),
            ('BILP-2026-B-003', 303, NULL, NULL, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 10:00:00', 'Examination / Assessment', 'IT2201 Departmental Examination', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-06 06:54:00', TIMESTAMP '2026-07-06 06:54:00', TIMESTAMP '2026-07-12 12:00:00'),
            ('BILP-2026-B-004', 304, NULL, 18, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 10:00:00', 'Examination / Assessment', 'IT2202 Departmental Examination', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-05 05:52:00', TIMESTAMP '2026-07-05 05:52:00', TIMESTAMP '2026-07-12 12:00:00'),
            ('BILP-2026-B-005', 305, NULL, NULL, '[{"equipmentIdentifier":77,"name":"Sony A7 IV","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 10:00:00', 'Examination / Assessment', 'IT2203 Departmental Examination', 'Approved', 'Low', NULL, '[]'::json, TIMESTAMP '2026-07-04 09:50:00', TIMESTAMP '2026-07-04 09:50:00', TIMESTAMP '2026-07-12 12:00:00'),
            ('BILP-2026-B-006', 306, NULL, NULL, '[{"equipmentIdentifier":78,"name":"Wireless Mic Kit","selectedQuantity":3,"quantity":3}]'::json, 3, TIMESTAMP '2026-07-12 09:30:00', 'Examination / Assessment', 'CCS1101 Departmental Examination', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-03 08:18:00', TIMESTAMP '2026-07-03 08:18:00', TIMESTAMP '2026-07-12 11:30:00'),
            ('BILP-2026-B-007', 307, NULL, NULL, '[{"equipmentIdentifier":78,"name":"Wireless Mic Kit","selectedQuantity":3,"quantity":3}]'::json, 3, TIMESTAMP '2026-07-12 10:00:00', 'Examination / Assessment', 'CCS2201 Departmental Examination', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-09 07:46:00', TIMESTAMP '2026-07-09 07:46:00', TIMESTAMP '2026-07-12 12:00:00'),
            ('BILP-2026-B-008', 308, NULL, 41, '[{"equipmentIdentifier":78,"name":"Wireless Mic Kit","selectedQuantity":3,"quantity":3}]'::json, 3, TIMESTAMP '2026-07-12 10:30:00', 'Examination / Assessment', 'CCS2202 Departmental Examination', 'Approved', 'Low', NULL, '[]'::json, TIMESTAMP '2026-07-08 07:14:00', TIMESTAMP '2026-07-08 07:14:00', TIMESTAMP '2026-07-12 12:30:00'),
            ('BILP-2026-B-009', 309, NULL, NULL, '[{"equipmentIdentifier":75,"name":"Canon EOS R50","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2026-07-12 09:00:00', 'Examination / Assessment', 'IT1201 Departmental Examination', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-07 04:42:00', TIMESTAMP '2026-07-07 04:42:00', TIMESTAMP '2026-07-12 11:00:00'),
            ('BILP-2026-B-010', 310, NULL, NULL, '[{"equipmentIdentifier":75,"name":"Canon EOS R50","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2026-07-12 09:30:00', 'Examination / Assessment', 'IT2301 Departmental Examination', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-06 09:10:00', TIMESTAMP '2026-07-06 09:10:00', TIMESTAMP '2026-07-12 11:30:00'),
            ('BILP-2026-B-011', 311, NULL, NULL, '[{"equipmentIdentifier":75,"name":"Canon EOS R50","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2026-07-12 10:00:00', 'Examination / Assessment', 'IT2302 Departmental Examination', 'Approved', 'Low', NULL, '[]'::json, TIMESTAMP '2026-07-05 08:38:00', TIMESTAMP '2026-07-05 08:38:00', TIMESTAMP '2026-07-12 12:00:00'),
            ('BILP-2026-B-012', 312, NULL, 21, '[{"equipmentIdentifier":82,"name":"PA Speaker Set","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2026-07-12 13:00:00', 'Examination / Assessment', 'IT3301 Departmental Examination', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-04 10:36:00', TIMESTAMP '2026-07-04 10:36:00', TIMESTAMP '2026-07-12 15:00:00'),
            ('BILP-2026-B-013', 313, NULL, NULL, '[{"equipmentIdentifier":82,"name":"PA Speaker Set","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2026-07-12 13:30:00', 'Examination / Assessment', 'IT3302 Departmental Examination', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-03 10:04:00', TIMESTAMP '2026-07-03 10:04:00', TIMESTAMP '2026-07-12 15:30:00'),
            ('BILP-2026-B-014', 314, NULL, NULL, '[{"equipmentIdentifier":82,"name":"PA Speaker Set","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2026-07-12 14:00:00', 'Examination / Assessment', 'IT3303 Departmental Examination', 'Approved', 'Low', NULL, '[]'::json, TIMESTAMP '2026-07-09 09:32:00', TIMESTAMP '2026-07-09 09:32:00', TIMESTAMP '2026-07-12 16:00:00'),
            ('BILP-2026-B-015', 315, NULL, NULL, '[{"equipmentIdentifier":79,"name":"LED Panel Light","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 10:00:00', 'Examination / Assessment', 'CCS3301 Departmental Examination', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-08 09:30:00', TIMESTAMP '2026-07-08 09:30:00', TIMESTAMP '2026-07-12 12:00:00'),
            ('BILP-2026-B-016', 316, NULL, 12, '[{"equipmentIdentifier":79,"name":"LED Panel Light","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 10:30:00', 'Examination / Assessment', 'CCS3302 Departmental Examination', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-07 08:58:00', TIMESTAMP '2026-07-07 08:58:00', TIMESTAMP '2026-07-12 12:30:00'),
            ('BILP-2026-B-017', 317, NULL, NULL, '[{"equipmentIdentifier":79,"name":"LED Panel Light","selectedQuantity":1,"quantity":1}]'::json, 1, TIMESTAMP '2026-07-12 11:00:00', 'Examination / Assessment', 'CCS3401 Departmental Examination', 'Approved', 'Low', NULL, '[]'::json, TIMESTAMP '2026-07-06 08:26:00', TIMESTAMP '2026-07-06 08:26:00', TIMESTAMP '2026-07-12 13:00:00'),
            ('BILP-2026-B-018', 318, NULL, NULL, '[{"equipmentIdentifier":111,"name":"Portable Monitor","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2026-07-12 15:00:00', 'Examination / Assessment', 'CCS3402 Departmental Examination', 'Approved', 'High', NULL, '[]'::json, TIMESTAMP '2026-07-05 11:24:00', TIMESTAMP '2026-07-05 11:24:00', TIMESTAMP '2026-07-12 17:00:00'),
            ('BILP-2026-B-019', 319, NULL, NULL, '[{"equipmentIdentifier":111,"name":"Portable Monitor","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2026-07-12 15:30:00', 'Examination / Assessment', 'IT4401 Departmental Examination', 'Approved', 'Normal', NULL, '[]'::json, TIMESTAMP '2026-07-04 10:52:00', TIMESTAMP '2026-07-04 10:52:00', TIMESTAMP '2026-07-12 17:30:00'),
            ('BILP-2026-B-020', 320, NULL, 25, '[{"equipmentIdentifier":111,"name":"Portable Monitor","selectedQuantity":2,"quantity":2}]'::json, 2, TIMESTAMP '2026-07-12 16:00:00', 'Examination / Assessment', 'IT4402 Departmental Examination', 'Approved', 'Low', NULL, '[]'::json, TIMESTAMP '2026-07-03 15:20:00', TIMESTAMP '2026-07-03 15:20:00', TIMESTAMP '2026-07-12 18:00:00')
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
    COALESCE(organization_name, 'Scenario B Analytics Demo') AS organization_name,
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
WHERE reservation_code LIKE 'BILP-2025-B-%'
   OR reservation_code LIKE 'BILP-2026-B-%';

-- Validation by year, status, and priority.
SELECT
    SUBSTRING(reservation_code FROM 'BILP-([0-9]{4})-B-') AS scenario_year,
    current_status,
    priority_level,
    COUNT(*) AS reservations
FROM reservations
WHERE reservation_code LIKE 'BILP-2025-B-%'
   OR reservation_code LIKE 'BILP-2026-B-%'
GROUP BY scenario_year, current_status, priority_level
ORDER BY scenario_year, current_status, priority_level;
