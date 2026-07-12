from datetime import date, datetime, timedelta
import json
from pathlib import Path
from typing import Any

from psycopg import Connection

from app.analytics_utils import json_dumps


DEMO_SEED_DIR = Path("/app/demo-seeds")
HIGH_LAST_LOW_THIS_SEED = DEMO_SEED_DIR / "HighToLow.sql"
HIGH_LAST_HIGH_THIS_SEED = DEMO_SEED_DIR / "HighToHigh.sql"
LOW_LAST_LOW_THIS_SEED = DEMO_SEED_DIR / "LowToLow.sql"
LOW_LAST_HIGH_THIS_SEED = DEMO_SEED_DIR / "LowToHigh.sql"
MIXED_SEED = DEMO_SEED_DIR / "Mixed.sql"
SCENARIO_A_SEED = DEMO_SEED_DIR / "ScenarioA_2025_2026.sql"
SCENARIO_B_SEED = DEMO_SEED_DIR / "ScenarioB_2025_2026.sql"


class ScenarioPreparer:
    def prepare_scenario(self, connection: Connection, scenario: str | None) -> None:
        normalized_scenario = (scenario or '').strip().lower()
        self._log_action(connection, 'prepare_scenario_received', normalized_scenario, {'scenario': normalized_scenario or None})
        if normalized_scenario in {'', 'manual', 'default'}:
            self._log_action(connection, 'prepare_scenario_skipped', normalized_scenario, {'reason': 'manual_or_default'})
            return

        if normalized_scenario == 'clean_data':
            self._clear_demo_analytics(connection)
            self._clear_all_reservations(connection)
            self._log_action(connection, 'clean_data_completed', normalized_scenario, {'reservations_cleared': True, 'analytics_cleared': True})
            connection.commit()
            return

        self._log_action(connection, 'scenario_cleanup_started', normalized_scenario, {'analytics_cleared': True, 'reservations_cleared': True})
        self._clear_demo_analytics(connection)
        self._clear_all_reservations(connection)
        if normalized_scenario == 'high_last_low_this':
            self._execute_seed_sql_file(connection, HIGH_LAST_LOW_THIS_SEED, normalized_scenario)
        elif normalized_scenario == 'high_last_high_this':
            self._execute_seed_sql_file(connection, HIGH_LAST_HIGH_THIS_SEED, normalized_scenario)
        elif normalized_scenario == 'low_last_low_this':
            self._execute_seed_sql_file(connection, LOW_LAST_LOW_THIS_SEED, normalized_scenario)
        elif normalized_scenario == 'low_last_high_this':
            self._execute_seed_sql_file(connection, LOW_LAST_HIGH_THIS_SEED, normalized_scenario)
        elif normalized_scenario == 'mixed':
            self._execute_seed_sql_file(connection, MIXED_SEED, normalized_scenario)
        elif normalized_scenario == 'scenario_a':
            self._execute_seed_sql_file(connection, SCENARIO_A_SEED, normalized_scenario)
        elif normalized_scenario == 'scenario_b':
            self._execute_seed_sql_file(connection, SCENARIO_B_SEED, normalized_scenario)

        seeded_row = connection.execute('SELECT COUNT(*) AS reservation_count FROM reservations').fetchone()
        seeded_count = int(seeded_row['reservation_count'] if seeded_row is not None else 0)
        self._log_action(connection, 'scenario_seed_completed', normalized_scenario, {'seededReservations': int(seeded_count or 0)})
        connection.commit()

    def _clear_all_reservations(self, connection: Connection) -> None:
        connection.execute('DELETE FROM reservations')

    def _clear_demo_analytics(self, connection: Connection) -> None:
        connection.execute('DELETE FROM analytics_results')
        connection.execute('DELETE FROM analytics_runs')
        connection.execute("DELETE FROM analytics_configurations WHERE config_key = 'daily_analytics'")
        self._ensure_action_log_table(connection)

    def _execute_seed_sql_file(self, connection: Connection, seed_path: Path, scenario_key: str) -> None:
        if not seed_path.is_file():
            raise FileNotFoundError(f"Analytics scenario seed file was not found: {seed_path}")

        sql = seed_path.read_text(encoding="utf-8")
        sql = "\n".join(
            line
            for line in sql.splitlines()
            if line.strip().upper() not in {"BEGIN;", "COMMIT;"}
        )

        connection.execute(sql)
        self._log_action(
            connection,
            'scenario_seed_file_executed',
            scenario_key,
            {'seedFile': str(seed_path), 'seedFileName': seed_path.name},
        )

    def _seed_scenario_reservations(self, connection: Connection, high_last_year: bool, high_this_sem: bool) -> None:
        templates = self._build_scenario_templates(high_last_year, high_this_sem)
        reservation_index = 1
        seeded_equipment_names: set[str] = set()
        for template in templates:
            repetitions = max(1, int(template["requested_quantity"] // 3))
            for copy_index in range(repetitions):
                reservation_code = f"SCN-{reservation_index:03d}"
                reservation_index += 1
                copy_quantity = max(1, int(template["requested_quantity"] - copy_index))
                for equipment_item in template["requested_equipment_list"]:
                    equipment_name = str(equipment_item.get("equipmentName", "")).strip()
                    if equipment_name:
                        seeded_equipment_names.add(equipment_name)
                connection.execute(
                    """
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
                        submission_timestamp,
                        updated_timestamp,
                        end_date_time
                    )
                    VALUES (%s, %s, %s, NULL, %s::json, %s, %s, %s, %s, %s, %s, %s, NOW(), %s)
                    ON CONFLICT (reservation_code) DO NOTHING
                        """,
                        (
                            reservation_code,
                            12,
                            template["organization_name"],
                            json.dumps(template["requested_equipment_list"], default=str),
                            copy_quantity,
                            template["event_date_time"],
                            template["purpose_description"],
                        template["activity_type"],
                        template["current_status"],
                        template["priority_level"],
                        template["submission_timestamp"],
                        template["end_date_time"],
                    ),
                )
        self._log_action(
            connection,
            'scenario_equipment_seeded',
            None,
            {
                'uniqueEquipmentCount': len(seeded_equipment_names),
                'equipmentNames': sorted(seeded_equipment_names),
            },
        )

    def _apply_scenario_readiness(self, connection: Connection, profile_key: str) -> None:
        rows = connection.execute(
            """
            SELECT equipment_identifier, equipment_name, total_quantity
              FROM equipment
             ORDER BY equipment_name ASC
            """
        ).fetchall()

        if not rows:
            self._log_action(connection, 'scenario_readiness_skipped', profile_key, {'reason': 'no_equipment'})
            return

        profiles = {
            'low_pressure': {'high': 1, 'medium': 2, 'low': 7},
            'moderate_pressure': {'high': 3, 'medium': 5, 'low': 10},
            'surprise_pressure': {'high': 5, 'medium': 7, 'low': 10},
            'high_pressure': {'high': 8, 'medium': 8, 'low': 8},
        }
        profile = profiles.get(profile_key, profiles['moderate_pressure'])

        connection.execute(
            """
            UPDATE equipment
               SET available_quantity = total_quantity,
                   equipment_state = 'Available',
                   operational_status = 'Available',
                   updated_at = NOW()
            """
        )

        high_rows = rows[:profile['high']]
        medium_rows = rows[profile['high']:profile['high'] + profile['medium']]
        low_rows = rows[profile['high'] + profile['medium']:profile['high'] + profile['medium'] + profile['low']]

        for row in high_rows:
            connection.execute(
                """
                UPDATE equipment
                   SET available_quantity = 0,
                       equipment_state = 'Under Maintenance',
                       operational_status = 'Under Maintenance',
                       updated_at = NOW()
                 WHERE equipment_identifier = %s
                """,
                (row['equipment_identifier'],),
            )

        for row in medium_rows:
            total_quantity = max(1, int(row['total_quantity'] or 1))
            connection.execute(
                """
                UPDATE equipment
                   SET available_quantity = %s,
                       equipment_state = 'Available',
                       operational_status = 'Available',
                       updated_at = NOW()
                 WHERE equipment_identifier = %s
                """,
                (max(1, round(total_quantity * 0.3)), row['equipment_identifier']),
            )

        for row in low_rows:
            total_quantity = max(1, int(row['total_quantity'] or 1))
            connection.execute(
                """
                UPDATE equipment
                   SET available_quantity = %s,
                       equipment_state = 'Available',
                       operational_status = 'Available',
                       updated_at = NOW()
                 WHERE equipment_identifier = %s
                """,
                (max(1, round(total_quantity * 0.6)), row['equipment_identifier']),
            )

        self._log_action(
            connection,
            'scenario_readiness_applied',
            profile_key,
            {
                'profile': profile_key,
                'highRiskEquipment': [row['equipment_name'] for row in high_rows],
                'mediumRiskCount': len(medium_rows),
                'lowRiskCount': len(low_rows),
                'veryLowRiskCount': max(0, len(rows) - len(high_rows) - len(medium_rows) - len(low_rows)),
            },
        )

    def _log_action(self, connection: Connection, action_key: str, scenario_key: str | None, payload: dict[str, Any] | None = None) -> None:
        self._ensure_action_log_table(connection)
        connection.execute(
            """
            INSERT INTO analytics_action_logs (
                action_key,
                scenario_key,
                action_status,
                action_payload
            )
            VALUES (%s, %s, %s, %s::jsonb)
            """,
            (
                action_key,
                scenario_key or None,
                'logged',
                json_dumps(payload or {}),
            ),
        )

    def _ensure_action_log_table(self, connection: Connection) -> None:
        connection.execute(
            """
            CREATE TABLE IF NOT EXISTS analytics_action_logs (
                analytics_action_log_identifier BIGINT GENERATED BY DEFAULT AS IDENTITY NOT NULL,
                action_key VARCHAR(120) NOT NULL,
                scenario_key VARCHAR(120) DEFAULT NULL,
                action_status VARCHAR(40) NOT NULL,
                action_payload JSONB DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOW() NOT NULL,
                PRIMARY KEY (analytics_action_log_identifier)
            )
            """
        )

    def _build_scenario_templates(self, high_last_year: bool, high_this_sem: bool) -> list[dict[str, Any]]:
        equipment_pool = [
            'Canon EOS R50',
            'Sony A7 IV',
            'DSLR Kit',
            'GoPro Action Kit',
            'Wireless Mic Kit',
            'Podcast Mic Set',
            'Portable Mixer',
            'Audio Interface',
            'PA Speaker Set',
            'PA Subwoofer',
            'LED Panel Light',
            'Stage Light Bar',
            'Ring Light Pro',
            'LED Tube Light',
            'Lighting Softbox',
            'Projector X200',
            'Projector Mini HD',
            'Portable TV Stand',
            'Portable Monitor',
            'HDMI Switcher',
            'Extension Cord 20m',
            'Extension Cord 50m',
            'Cable Kit Pro',
            'Battery Pack 20k',
            'Document Scanner',
            'Tablet Cart',
            'Wireless Presenter',
            'Tripod Pro',
            'Tripod Mini',
            'Camera Slider',
        ]
        if high_last_year and high_this_sem:
            dates = self._build_plateau_pairs()
        elif high_last_year and not high_this_sem:
            dates = self._build_spikes_pairs()
        elif not high_last_year and high_this_sem:
            dates = self._build_plateau_with_valleys_pairs()
        else:
            dates = self._build_spikes_then_plateau_pairs()
        templates: list[dict[str, Any]] = []

        for index, (event_date, quantity, organization_name) in enumerate(dates):
            pool_offset = (index * 3) % len(equipment_pool)
            equipment_mix = [
                {'equipmentName': equipment_pool[pool_offset % len(equipment_pool)], 'quantity': 1},
                {'equipmentName': equipment_pool[(pool_offset + 1) % len(equipment_pool)], 'quantity': max(1, quantity // 3)},
                {'equipmentName': equipment_pool[(pool_offset + 2) % len(equipment_pool)], 'quantity': 1},
            ]
            if quantity >= 8:
                equipment_mix.append({'equipmentName': equipment_pool[(pool_offset + 3) % len(equipment_pool)], 'quantity': max(1, quantity // 4)})
            if quantity >= 10:
                equipment_mix.append({'equipmentName': equipment_pool[(pool_offset + 4) % len(equipment_pool)], 'quantity': 1})

            templates.append(
                {
                    'organization_name': organization_name,
                    'requested_equipment_list': equipment_mix,
                    'requested_quantity': quantity,
                    'event_date_time': datetime.combine(event_date, datetime.min.time()),
                    'purpose_description': 'Scenario-driven analytics test request',
                    'activity_type': 'Event',
                    'current_status': 'Pending Review' if quantity >= 8 else 'Approved',
                    'priority_level': 'High' if quantity >= 8 else 'Normal',
                    'submission_timestamp': datetime.combine(event_date - timedelta(days=7), datetime.min.time()),
                    'end_date_time': datetime.combine(event_date, datetime.min.time()) + timedelta(hours=4),
                }
            )

        return templates

    def _build_spikes_pairs(self) -> list[tuple[date, int, str]]:
        pattern: list[tuple[date, int, str]] = []
        calendar_days = [(5, 25), (5, 27), (5, 29), (6, 1), (6, 4), (6, 7), (6, 10), (6, 13), (6, 16), (6, 19), (6, 22)]
        for index, (month, day) in enumerate(calendar_days):
            last_year_quantity = 2
            this_sem_quantity = 2
            if index in {2, 5, 8}:
                last_year_quantity = 14
            if index in {1, 4, 7, 10}:
                last_year_quantity = 11
            pattern.append((date(2025, month, day), last_year_quantity, 'Enrollment Office'))
            pattern.append((date(2026, month, day), this_sem_quantity, 'Enrollment Office'))
        return pattern

    def _build_plateau_pairs(self) -> list[tuple[date, int, str]]:
        pattern: list[tuple[date, int, str]] = []
        calendar_days = [(5, 25), (5, 27), (5, 29), (6, 1), (6, 4), (6, 7), (6, 10), (6, 13), (6, 16), (6, 19), (6, 22)]
        for index, (month, day) in enumerate(calendar_days):
            last_year_quantity = 9
            this_sem_quantity = 9
            pattern.append((date(2025, month, day), last_year_quantity, 'Enrollment Office'))
            pattern.append((date(2026, month, day), this_sem_quantity, 'Enrollment Office'))
        return pattern

    def _build_plateau_with_valleys_pairs(self) -> list[tuple[date, int, str]]:
        pattern: list[tuple[date, int, str]] = []
        calendar_days = [(5, 25), (5, 27), (5, 29), (6, 1), (6, 4), (6, 7), (6, 10), (6, 13), (6, 16), (6, 19), (6, 22)]
        for index, (month, day) in enumerate(calendar_days):
            last_year_quantity = 3
            this_sem_quantity = 10
            if index in {3, 7}:
                last_year_quantity = 2
            if index in {4, 8}:
                this_sem_quantity = 4
            if index in {1, 5, 9}:
                this_sem_quantity = 3
            pattern.append((date(2025, month, day), last_year_quantity, 'Enrollment Office'))
            pattern.append((date(2026, month, day), this_sem_quantity, 'Enrollment Office'))
        return pattern

    def _build_spikes_then_plateau_pairs(self) -> list[tuple[date, int, str]]:
        pattern: list[tuple[date, int, str]] = []
        calendar_days = [(5, 25), (5, 27), (5, 29), (6, 1), (6, 4), (6, 7), (6, 10), (6, 13), (6, 16), (6, 19), (6, 22)]
        for index, (month, day) in enumerate(calendar_days):
            last_year_quantity = 13 if index < 4 else 9
            this_sem_quantity = 13 if index < 4 else 9
            pattern.append((date(2025, month, day), last_year_quantity, 'Enrollment Office'))
            pattern.append((date(2026, month, day), this_sem_quantity, 'Enrollment Office'))

        return pattern

