from copy import deepcopy
from datetime import UTC, datetime
from typing import Any
from uuid import uuid4

from psycopg import Connection

from app.allocation_snapshot import AllocationSnapshotBuilder
from app.analytics_utils import json_dumps
from app.forecast_snapshot import ForecastSnapshotBuilder
from app.readiness_snapshot import ReadinessSnapshotBuilder
from app.scenario_preparer import ScenarioPreparer


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
        "model": "bilp",
        "historyDays": 30,
        "objective": "maximize_fulfillment",
    },
}


class AnalyticsRunner:
    def __init__(self) -> None:
        self.forecast_builder = ForecastSnapshotBuilder()
        self.readiness_builder = ReadinessSnapshotBuilder()
        self.allocation_builder = AllocationSnapshotBuilder()
        self.scenario_preparer = ScenarioPreparer()

    def prepare_scenario(self, connection: Connection, scenario: str | None) -> None:
        self.scenario_preparer.prepare_scenario(connection, scenario)

    def analyze_range(
        self,
        connection: Connection,
        history_days: int,
        start_date: str,
        end_date: str,
    ) -> dict[str, Any]:
        config = self._build_run_config(
            connection,
            history_days=history_days,
            start_date=start_date,
            end_date=end_date,
        )
        generated_at = datetime.now(UTC)

        return {
            "status": "completed",
            "startedAt": generated_at.isoformat(),
            "completedAt": generated_at.isoformat(),
            "results": self._build_results(connection, config),
        }

    def run_daily_check(
        self,
        connection: Connection,
        triggered_by: str = "scheduler",
        history_days: int | None = None,
        start_date: str | None = None,
        end_date: str | None = None,
    ) -> dict[str, Any]:
        config = self._build_run_config(connection, history_days, start_date, end_date)
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
            (run_id, "daily_check", "running", triggered_by, json_dumps(config), started_at),
        )

        try:
            results = self._build_results(connection, config)

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
                        json_dumps(payload),
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
                    json_dumps(
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

            return {
                "runIdentifier": run_id,
                "status": "completed",
                "startedAt": started_at.isoformat(),
                "completedAt": completed_at.isoformat(),
                "results": results,
            }
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

    def _build_run_config(
        self,
        connection: Connection,
        history_days: int | None = None,
        start_date: str | None = None,
        end_date: str | None = None,
    ) -> dict[str, Any]:
        config = deepcopy(self._load_or_create_config(connection))
        if history_days is not None:
            normalized_history_days = max(1, int(history_days))
            config.setdefault("forecast", {})["historyDays"] = normalized_history_days
            config.setdefault("forecast", {})["forecastDays"] = 3
            config.setdefault("allocation", {})["historyDays"] = normalized_history_days
        if start_date and end_date:
            config["dateRange"] = {
                "startDate": start_date,
                "endDate": end_date,
            }

        return config

    def _build_results(self, connection: Connection, config: dict[str, Any]) -> dict[str, Any]:
        return {
            "forecast": self.forecast_builder.build(connection, config),
            "readiness": self.readiness_builder.build(connection),
            "allocation": self.allocation_builder.build(connection, config),
        }

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
            ("daily_analytics", json_dumps(DEFAULT_CONFIG)),
        )
        return DEFAULT_CONFIG
