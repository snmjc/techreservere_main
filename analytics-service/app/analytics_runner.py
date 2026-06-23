from datetime import UTC, date, datetime, timedelta
import json
from typing import Any
from uuid import uuid4

from psycopg import Connection


DEFAULT_CONFIG = {
    "forecast": {
        "enabled": True,
        "model": "sarima",
        "historyDays": 180,
        "forecastDays": 30,
        "seasonalPeriod": 7,
    },
    "readiness": {
        "enabled": True,
        "model": "random_forest",
        "riskThreshold": 0.65,
    },
    "allocation": {
        "enabled": True,
        "model": "binary_linear_programming",
        "objective": "maximize_fulfillment",
    },
}


class AnalyticsRunner:
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
            self._seed_scenario_reservations(connection, high_last_year=True, high_this_sem=False)
        elif normalized_scenario == 'high_last_high_this':
            self._seed_scenario_reservations(connection, high_last_year=True, high_this_sem=True)
        elif normalized_scenario == 'low_last_low_this':
            self._seed_scenario_reservations(connection, high_last_year=False, high_this_sem=False)
        elif normalized_scenario == 'low_last_high_this':
            self._seed_scenario_reservations(connection, high_last_year=False, high_this_sem=True)

        seeded_row = connection.execute('SELECT COUNT(*) AS reservation_count FROM reservations').fetchone()
        seeded_count = int(seeded_row['reservation_count'] if seeded_row is not None else 0)
        self._log_action(connection, 'scenario_seed_completed', normalized_scenario, {'seededReservations': int(seeded_count or 0)})
        connection.commit()

    def run_daily_check(self, connection: Connection, triggered_by: str = "scheduler") -> dict[str, Any]:
        config = self._load_or_create_config(connection)
        run_id = str(uuid4())
        started_at = datetime.now(UTC)

        connection.execute(
            """
            INSERT INTO analytics_runs (
                run_identifier,
                run_type,
                status,
                triggered_by,
                config_snapshot,
                started_at
            )
            VALUES (%s, %s, %s, %s, %s::jsonb, %s)
            """,
            (run_id, "daily_check", "running", triggered_by, _json(config), started_at),
        )

        try:
            results = {
                "forecast": self._build_forecast_snapshot(connection, config),
                "readiness": self._build_readiness_snapshot(connection),
                "allocation": self._build_allocation_snapshot(connection),
            }

            for result_type, payload in results.items():
                connection.execute(
                    """
                    INSERT INTO analytics_results (
                        run_identifier,
                        result_type,
                        model_name,
                        result_payload,
                        generated_at
                    )
                    VALUES (%s, %s, %s, %s::jsonb, %s)
                    """,
                    (
                        run_id,
                        result_type,
                        payload["modelName"],
                        _json(payload),
                        started_at,
                    ),
                )

            completed_at = datetime.now(UTC)
            connection.execute(
                """
                UPDATE analytics_runs
                   SET status = %s,
                       completed_at = %s,
                       summary_payload = %s::jsonb
                 WHERE run_identifier = %s
                """,
                (
                    "completed",
                    completed_at,
                    _json(
                        {
                            "resultTypes": list(results.keys()),
                            "startedAt": started_at.isoformat(),
                            "completedAt": completed_at.isoformat(),
                        }
                    ),
                    run_id,
                ),
            )
            connection.commit()

            return {"runIdentifier": run_id, "status": "completed", "results": results}
        except Exception as error:
            connection.execute(
                """
                UPDATE analytics_runs
                   SET status = %s,
                       completed_at = %s,
                       error_message = %s
                 WHERE run_identifier = %s
                """,
                ("failed", datetime.now(UTC), str(error), run_id),
            )
            connection.commit()
            raise

    def _load_or_create_config(self, connection: Connection) -> dict[str, Any]:
        row = connection.execute(
            """
            SELECT config_payload
              FROM analytics_configurations
             WHERE config_key = %s
               AND is_active = TRUE
             ORDER BY updated_at DESC
             LIMIT 1
            """,
            ("daily_analytics",),
        ).fetchone()

        if row is not None:
            return dict(row["config_payload"])

        connection.execute(
            """
            INSERT INTO analytics_configurations (config_key, config_payload, is_active)
            VALUES (%s, %s::jsonb, TRUE)
            """,
            ("daily_analytics", _json(DEFAULT_CONFIG)),
        )
        return DEFAULT_CONFIG

    def _build_forecast_snapshot(self, connection: Connection, config: dict[str, Any]) -> dict[str, Any]:
        history_days = int(config.get("forecast", {}).get("historyDays", 180))
        forecast_days = int(config.get("forecast", {}).get("forecastDays", 30))
        start_date = date.today() - timedelta(days=history_days)
        rows = connection.execute(
            """
            SELECT DATE(submission_timestamp) AS demand_date,
                   COUNT(*)::int AS demand_count
              FROM reservations
             WHERE submission_timestamp >= %s
             GROUP BY DATE(submission_timestamp)
             ORDER BY demand_date
            """,
            (start_date,),
        ).fetchall()

        series = [
            {"date": row["demand_date"].isoformat(), "value": row["demand_count"]}
            for row in rows
        ]

        forecast_series = self._build_forecast_series(series, forecast_days)
        forecast_peak = max(forecast_series, key=lambda item: item["value"], default=None)
        average_actual = _average([item["value"] for item in series], 0.0)
        average_forecast = _average([item["value"] for item in forecast_series], 0.0)
        expected_change_percent = 0.0
        if average_actual > 0:
            expected_change_percent = round(((average_forecast - average_actual) / average_actual) * 100, 2)

        return {
            "modelName": "sarima",
            "status": "placeholder_ready",
            "actualSeries": series,
            "forecastSeries": forecast_series,
            "forecastPeak": forecast_peak,
            "summary": {
                "averageActualDemand": round(average_actual, 2),
                "averageForecastDemand": round(average_forecast, 2),
                "expectedChangePercent": expected_change_percent,
            },
            "notes": "FastAPI storage path is live. SARIMA training will replace this placeholder output.",
        }

    def _build_forecast_series(self, actual_series: list[dict[str, Any]], forecast_days: int) -> list[dict[str, Any]]:
        if forecast_days <= 0:
            return []

        if not actual_series:
            return []

        sorted_series = sorted(actual_series, key=lambda item: item["date"])
        values = [float(item["value"]) for item in sorted_series]
        baseline = _average(values, 0.0)
        recent_baseline = _average(values[-14:], baseline)
        weekday_totals: dict[int, list[float]] = {}
        month_totals: dict[int, list[float]] = {}
        for item in sorted_series:
            series_date = date.fromisoformat(item["date"])
            value = float(item["value"])
            weekday_totals.setdefault(series_date.weekday(), []).append(value)
            month_totals.setdefault(series_date.month, []).append(value)

        last_date = date.fromisoformat(sorted_series[-1]["date"])
        forecast_series: list[dict[str, Any]] = []

        for offset in range(1, forecast_days + 1):
            forecast_date = last_date + timedelta(days=offset)
            weekday_average = _average(weekday_totals.get(forecast_date.weekday(), []), baseline)
            month_average = _average(month_totals.get(forecast_date.month, []), baseline)
            seasonal_boost = 1.0
            if forecast_date.month in (5, 6):
                seasonal_boost = 1.25
            elif forecast_date.month in (1, 2):
                seasonal_boost = 0.9

            trend_adjustment = ((recent_baseline - baseline) / max(1.0, baseline)) * 0.35 if baseline > 0 else 0.0
            projected_value = round(
                max(
                    0.0,
                    (weekday_average * 0.35 + month_average * 0.45 + recent_baseline * 0.20)
                    * seasonal_boost
                    * (1 + trend_adjustment),
                ),
                2,
            )
            forecast_series.append(
                {
                    "date": forecast_date.isoformat(),
                    "value": projected_value,
                }
            )

        return forecast_series

    def _build_readiness_snapshot(self, connection: Connection) -> dict[str, Any]:
        rows = connection.execute(
            """
            SELECT equipment_identifier,
                   equipment_name,
                   equipment_state,
                   operational_status,
                   total_quantity,
                   available_quantity
              FROM equipment
             ORDER BY equipment_identifier
            """
        ).fetchall()

        records = []
        for row in rows:
            total_quantity = max(0, int(row["total_quantity"] or 0))
            available_quantity = max(0, int(row["available_quantity"] or 0))
            availability_ratio = available_quantity / total_quantity if total_quantity > 0 else 1
            risk_label = "ready" if availability_ratio >= 0.7 else "watch"
            if row["equipment_state"].lower() in {"under maintenance", "unavailable"}:
                risk_label = "watch"

            records.append(
                {
                    "equipmentIdentifier": row["equipment_identifier"],
                    "equipmentName": row["equipment_name"],
                    "readinessLabel": risk_label,
                    "availabilityRatio": round(availability_ratio, 3),
                    "equipmentState": row["equipment_state"],
                    "operationalStatus": row["operational_status"],
                }
            )

        return {
            "modelName": "random_forest",
            "status": "placeholder_ready",
            "records": records,
            "riskSummary": {
                "readyCount": sum(1 for item in records if item["readinessLabel"] == "ready"),
                "watchCount": sum(1 for item in records if item["readinessLabel"] == "watch"),
            },
            "notes": "FastAPI storage path is live. Random Forest inference will replace this placeholder output.",
        }

    def _build_allocation_snapshot(self, connection: Connection) -> dict[str, Any]:
        pending_rows = connection.execute(
            """
            SELECT reservation_identifier,
                   reservation_code,
                   organization_name,
                   requested_equipment_list,
                   requested_quantity,
                   event_date_time,
                   current_status,
                   priority_level,
                   submission_timestamp
              FROM reservations
             WHERE LOWER(COALESCE(current_status, '')) IN ('pending', 'pending review')
             ORDER BY submission_timestamp ASC, event_date_time ASC
            """
        ).fetchall()

        equipment_rows = connection.execute(
            """
            SELECT equipment_identifier,
                   equipment_name,
                   available_quantity,
                   operational_status,
                   equipment_state
              FROM equipment
             ORDER BY available_quantity DESC, equipment_name ASC
            """
        ).fetchall()

        allocation_plan: list[dict[str, Any]] = []
        remaining_capacity = {
            int(row["equipment_identifier"]): max(0, int(row["available_quantity"] or 0))
            for row in equipment_rows
        }
        for request in pending_rows:
            requested_items = request["requested_equipment_list"] or []
            if isinstance(requested_items, str):
                import json
                requested_items = json.loads(requested_items)

            line_items: list[dict[str, Any]] = []
            allocated_total = 0
            for item in requested_items:
                item_name = str(item.get("equipmentName") or item.get("name") or "Unknown")
                item_quantity = max(0, int(item.get("quantity") or 0))
                allocated_quantity = 0

                for equipment in equipment_rows:
                    if remaining_capacity[int(equipment["equipment_identifier"])] <= 0:
                        continue
                    if equipment["equipment_name"].lower() != item_name.lower():
                        continue
                    allocated_quantity = min(item_quantity, remaining_capacity[int(equipment["equipment_identifier"])])
                    remaining_capacity[int(equipment["equipment_identifier"])] -= allocated_quantity
                    break

                allocated_total += allocated_quantity
                line_items.append(
                    {
                        "equipmentName": item_name,
                        "requestedQuantity": item_quantity,
                        "allocatedQuantity": allocated_quantity,
                    }
                )

            allocation_plan.append(
                {
                    "reservationCode": request["reservation_code"],
                    "organizationName": request["organization_name"],
                    "eventDate": request["event_date_time"].date().isoformat(),
                    "priorityLevel": request["priority_level"],
                    "requestedQuantity": int(request["requested_quantity"] or 0),
                    "allocatedQuantity": allocated_total,
                    "lineItems": line_items,
                    "status": "partial" if allocated_total < int(request["requested_quantity"] or 0) else "allocated",
                }
            )

        pending_count = len(pending_rows)

        return {
            "modelName": "binary_linear_programming",
            "status": "placeholder_ready",
            "pendingRequestCount": pending_count,
            "allocationPlan": allocation_plan,
            "fulfilledCount": sum(1 for item in allocation_plan if item["status"] == "allocated"),
            "partialCount": sum(1 for item in allocation_plan if item["status"] == "partial"),
            "notes": "FastAPI storage path is live. Binary LP solving will replace this placeholder output.",
        }

    def _clear_all_reservations(self, connection: Connection) -> None:
        connection.execute('DELETE FROM reservations')

    def _clear_demo_analytics(self, connection: Connection) -> None:
        connection.execute('DELETE FROM analytics_results')
        connection.execute('DELETE FROM analytics_runs')
        connection.execute("DELETE FROM analytics_configurations WHERE config_key = 'daily_analytics'")
        self._ensure_action_log_table(connection)

    def _seed_scenario_reservations(self, connection: Connection, high_last_year: bool, high_this_sem: bool) -> None:
        templates = self._build_scenario_templates(high_last_year, high_this_sem)
        reservation_index = 1
        for template in templates:
            repetitions = max(1, int(template["requested_quantity"] // 3))
            for copy_index in range(repetitions):
                reservation_code = f"SCN-{reservation_index:03d}"
                reservation_index += 1
                copy_quantity = max(1, int(template["requested_quantity"] - copy_index))
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
                            json.dumps([
                                {'equipmentName': 'Canon EOS R50', 'quantity': 1},
                                {'equipmentName': 'Wireless Mic Kit', 'quantity': max(1, copy_quantity // 2)},
                            ], default=str),
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
                _json(payload or {}),
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
            equipment_mix = [
                {'equipmentName': 'Canon EOS R50', 'quantity': 1},
                {'equipmentName': 'Wireless Mic Kit', 'quantity': max(1, quantity // 3)},
                {'equipmentName': 'Tripod Pro', 'quantity': 1},
            ]
            if index % 2 == 0:
                equipment_mix.append({'equipmentName': 'Portable Projector', 'quantity': max(1, quantity // 4)})
            if quantity >= 10:
                equipment_mix.append({'equipmentName': 'PA Speaker', 'quantity': 1})

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


def _json(value: dict[str, Any] | list[Any]) -> str:
    return json.dumps(value, default=str)


def _average(values: list[Any], fallback: float = 0.0) -> float:
    numeric_values = [float(value) for value in values if value is not None]
    if not numeric_values:
        return fallback
    return sum(numeric_values) / len(numeric_values)
