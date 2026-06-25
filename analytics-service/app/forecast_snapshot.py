from datetime import date, timedelta
from typing import Any

from psycopg import Connection

from app.analytics_utils import average


class ForecastSnapshotBuilder:
    def build(self, connection: Connection, config: dict[str, Any]) -> dict[str, Any]:
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
        average_actual = average([item["value"] for item in series], 0.0)
        average_forecast = average([item["value"] for item in forecast_series], 0.0)
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
        if forecast_days <= 0 or not actual_series:
            return []

        sorted_series = sorted(actual_series, key=lambda item: item["date"])
        values = [float(item["value"]) for item in sorted_series]
        baseline = average(values, 0.0)
        recent_baseline = average(values[-14:], baseline)
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
            weekday_average = average(weekday_totals.get(forecast_date.weekday(), []), baseline)
            month_average = average(month_totals.get(forecast_date.month, []), baseline)
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
            forecast_series.append({"date": forecast_date.isoformat(), "value": projected_value})

        return forecast_series
