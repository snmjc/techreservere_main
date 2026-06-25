from datetime import UTC, date, datetime, timedelta
import json
from pathlib import Path
from typing import Any
from uuid import uuid4

from psycopg import Connection


DEMO_SEED_DIR = Path("/app/demo-seeds")
HIGH_LAST_LOW_THIS_SEED = DEMO_SEED_DIR / "HighToLow.sql"
HIGH_LAST_HIGH_THIS_SEED = DEMO_SEED_DIR / "HighToHigh.sql"
LOW_LAST_LOW_THIS_SEED = DEMO_SEED_DIR / "LowToLow.sql"


DEFAULT_CONFIG = {
        "forecast": {
            "enabled": True,
            "model": "sarima",
            "historyDays": 180,
            "forecastDays": 3,
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
            self._execute_seed_sql_file(connection, HIGH_LAST_LOW_THIS_SEED, normalized_scenario)
        elif normalized_scenario == 'high_last_high_this':
            self._execute_seed_sql_file(connection, HIGH_LAST_HIGH_THIS_SEED, normalized_scenario)
        elif normalized_scenario == 'low_last_low_this':
            self._execute_seed_sql_file(connection, LOW_LAST_LOW_THIS_SEED, normalized_scenario)
        elif normalized_scenario == 'low_last_high_this':
            self._seed_scenario_reservations(connection, high_last_year=False, high_this_sem=True)
            self._apply_scenario_readiness(connection, 'surprise_pressure')

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
                "forecastHorizonDays": forecast_days,
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
        bands = {
            "High Risk": {"label": "High Risk", "count": 0, "color": "#ef4444"},
            "Medium Risk": {"label": "Medium Risk", "count": 0, "color": "#f59e0b"},
            "Low Risk": {"label": "Low Risk", "count": 0, "color": "#facc15"},
            "Very Low Risk": {"label": "Very Low Risk", "count": 0, "color": "#16a34a"},
        }
        factor_counts = {
            "Low stock pressure": 0,
            "Inactive availability state": 0,
            "High usage frequency": 0,
            "Overdue release linkage": 0,
        }
        high_risk_equipment = []
        for row in rows:
            total_quantity = max(0, int(row["total_quantity"] or 0))
            available_quantity = max(0, int(row["available_quantity"] or 0))
            availability_ratio = available_quantity / total_quantity if total_quantity > 0 else 1
            risk_label = "ready" if availability_ratio >= 0.7 else "watch"
            equipment_state = str(row["equipment_state"] or "")
            operational_status = str(row["operational_status"] or "")
            is_inactive = equipment_state.lower() in {"under maintenance", "unavailable"} or operational_status.lower() in {"under maintenance", "unavailable"}
            if is_inactive:
                risk_label = "watch"

            score = 0
            if availability_ratio <= 0.2:
                score += 3
                factor_counts["Low stock pressure"] += 1
            elif availability_ratio <= 0.45:
                score += 4
                factor_counts["Low stock pressure"] += 1
            elif availability_ratio <= 0.7:
                score += 2

            if is_inactive:
                score += 3
                factor_counts["Inactive availability state"] += 1

            if score >= 6:
                band_label = "High Risk"
                high_risk_equipment.append(
                    {
                        "name": row["equipment_name"],
                        "score": score,
                        "usageCount": 0,
                    }
                )
            elif score >= 4:
                band_label = "Medium Risk"
            elif score >= 2:
                band_label = "Low Risk"
            else:
                band_label = "Very Low Risk"

            bands[band_label]["count"] += 1

            records.append(
                {
                    "equipmentIdentifier": row["equipment_identifier"],
                    "equipmentName": row["equipment_name"],
                    "readinessLabel": risk_label,
                    "availabilityRatio": round(availability_ratio, 3),
                    "equipmentState": equipment_state,
                    "operationalStatus": operational_status,
                }
            )

        ordered_bands = [bands["High Risk"], bands["Medium Risk"], bands["Low Risk"], bands["Very Low Risk"]]
        total_equipment = sum(item["count"] for item in ordered_bands)
        safe_equipment = bands["Low Risk"]["count"] + bands["Very Low Risk"]["count"]
        sorted_factors = sorted(factor_counts.items(), key=lambda item: item[1], reverse=True)

        return {
            "modelName": "random_forest",
            "status": "placeholder_ready",
            "records": records,
            "bands": ordered_bands,
            "topRiskFactors": [factor for factor, _count in sorted_factors[:4]],
            "highRiskEquipment": high_risk_equipment[:5],
            "safeRate": round((safe_equipment / total_equipment) * 100, 1) if total_equipment > 0 else 0,
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
                   equipment_category,
                   total_quantity,
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
        utilization_by_category = self._build_utilization_by_category(equipment_rows, self._build_equipment_usage_map(connection, 2026))
        utilization_comparison_by_category = self._build_utilization_by_category(equipment_rows, self._build_equipment_usage_map(connection, 2025))
        top_equipment = self._build_top_equipment(equipment_rows, self._build_equipment_usage_map(connection, 2026))

        return {
            "modelName": "binary_linear_programming",
            "status": "placeholder_ready",
            "pendingRequestCount": pending_count,
            "allocationPlan": allocation_plan,
            "utilizationByCategory": utilization_by_category,
            "utilizationComparisonByCategory": utilization_comparison_by_category,
            "topEquipment": top_equipment,
            "summary": {
                "totalEquipment": len(equipment_rows),
                "activeReservations": len(allocation_plan),
                "pendingRequests": pending_count,
                "completedThisPeriod": 0,
            },
            "fulfilledCount": sum(1 for item in allocation_plan if item["status"] == "allocated"),
            "partialCount": sum(1 for item in allocation_plan if item["status"] == "partial"),
            "notes": "FastAPI storage path is live. Binary LP solving will replace this placeholder output.",
        }

    def _build_equipment_usage_map(self, connection: Connection, year: int) -> dict[str, int]:
        rows = connection.execute(
            """
            SELECT requested_equipment_list
              FROM reservations
             WHERE EXTRACT(YEAR FROM event_date_time) = %s
               AND LOWER(COALESCE(current_status, '')) NOT IN ('cancelled', 'rejected')
            """,
            (year,),
        ).fetchall()

        usage: dict[str, int] = {}
        for row in rows:
            requested_items = row["requested_equipment_list"] or []
            if isinstance(requested_items, str):
                requested_items = json.loads(requested_items)
            for item in requested_items:
                item_name = str(item.get("equipmentName") or item.get("name") or "").strip()
                if not item_name:
                    continue
                usage[item_name.lower()] = usage.get(item_name.lower(), 0) + max(1, int(item.get("quantity") or 1))

        return usage

    def _build_utilization_by_category(self, equipment_rows: list[Any], usage_map: dict[str, int]) -> list[dict[str, Any]]:
        category_usage: dict[str, int] = {}
        category_total: dict[str, int] = {}

        for row in equipment_rows:
            category = str(row["equipment_category"] or "Others")
            equipment_name = str(row["equipment_name"] or "")
            total_quantity = max(1, int(row["total_quantity"] or 1))
            category_total[category] = category_total.get(category, 0) + total_quantity
            category_usage[category] = category_usage.get(category, 0) + usage_map.get(equipment_name.lower(), 0)

        result = [
            {
                "label": category,
                "value": round(min(100.0, (category_usage.get(category, 0) / max(1, total)) * 100), 1),
            }
            for category, total in category_total.items()
        ]
        return sorted(result, key=lambda item: (-float(item["value"]), str(item["label"])))[:5]

    def _build_top_equipment(self, equipment_rows: list[Any], usage_map: dict[str, int]) -> list[dict[str, Any]]:
        items = []
        for row in equipment_rows:
            equipment_name = str(row["equipment_name"] or "")
            usage_count = usage_map.get(equipment_name.lower(), 0)
            if usage_count <= 0:
                continue
            total_quantity = max(1, int(row["total_quantity"] or 1))
            items.append(
                {
                    "name": equipment_name,
                    "count": usage_count,
                    "rate": round(min(100.0, (usage_count / total_quantity) * 100), 1),
                }
            )
        return sorted(items, key=lambda item: (-int(item["count"]), str(item["name"])))[:5]

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


def _json(value: dict[str, Any] | list[Any]) -> str:
    return json.dumps(value, default=str)


def _average(values: list[Any], fallback: float = 0.0) -> float:
    numeric_values = [float(value) for value in values if value is not None]
    if not numeric_values:
        return fallback
    return sum(numeric_values) / len(numeric_values)
