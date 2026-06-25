from datetime import date, timedelta
from typing import Any

from psycopg import Connection

from app.analytics_utils import average


class ForecastSnapshotBuilder:
    def build(self, connection: Connection, config: dict[str, Any]) -> dict[str, Any]:
        history_days = int(config.get("forecast", {}).get("historyDays", 180))
        forecast_days = int(config.get("forecast", {}).get("forecastDays", 30))
        configured_range = config.get("dateRange", {})
        start_date = date.fromisoformat(configured_range["startDate"]) if configured_range.get("startDate") else (
            date.today() - timedelta(days=max(0, history_days - 1))
        )
        end_date = date.fromisoformat(configured_range["endDate"]) if configured_range.get("endDate") else date.today()
        rows = connection.execute(
            """
            SELECT DATE(submission_timestamp) AS demand_date,
                   COUNT(*)::int AS demand_count
             FROM reservations
             WHERE submission_timestamp >= %s
               AND submission_timestamp < %s
             GROUP BY DATE(submission_timestamp)
             ORDER BY demand_date
            """,
            (start_date, end_date + timedelta(days=1)),
        ).fetchall()

        demand_by_date = {
            row["demand_date"]: int(row["demand_count"])
            for row in rows
        }
        series = []
        current_date = start_date
        while current_date <= end_date:
            series.append({
                "date": current_date.isoformat(),
                "value": demand_by_date.get(current_date, 0),
            })
            current_date += timedelta(days=1)

        forecast_series = self._build_forecast_series(series, forecast_days)
        history_series = self._build_history_series(
            connection,
            start_date,
            end_date,
            forecast_days,
        )
        future_forecast_series = forecast_series[-forecast_days:] if forecast_days > 0 else []
        forecast_peak = max(future_forecast_series, key=lambda item: item["value"], default=None)
        average_actual = average([item["value"] for item in series], 0.0)
        average_forecast = average([item["value"] for item in future_forecast_series], 0.0)
        expected_change_percent = 0.0
        if average_actual > 0:
            expected_change_percent = round(((average_forecast - average_actual) / average_actual) * 100, 2)

        return {
            "modelName": "sarima",
            "status": "placeholder_ready",
            "actualSeries": series,
            "forecastSeries": forecast_series,
            "historySeries": history_series,
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
        if not actual_series:
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

        forecast_series: list[dict[str, Any]] = []
        first_date = date.fromisoformat(sorted_series[0]["date"])
        total_days = len(sorted_series) + max(0, forecast_days)
        for offset in range(total_days):
            forecast_date = first_date + timedelta(days=offset)
            projected_value = self._project_value(
                forecast_date,
                weekday_totals,
                month_totals,
                baseline,
                recent_baseline,
            )
            forecast_series.append({"date": forecast_date.isoformat(), "value": projected_value})

        return forecast_series

    def _build_history_series(
        self,
        connection: Connection,
        start_date: date,
        end_date: date,
        forecast_days: int,
    ) -> list[dict[str, Any]]:
        display_end_date = end_date + timedelta(days=max(0, forecast_days))
        history_dates = []
        current_date = start_date
        while current_date <= display_end_date:
            history_dates.append(self._previous_year_date(current_date))
            current_date += timedelta(days=1)

        history_start = min(history_dates)
        history_end = max(history_dates)
        rows = connection.execute(
            """
            SELECT DATE(submission_timestamp) AS demand_date,
                   COUNT(*)::int AS demand_count
              FROM reservations
             WHERE submission_timestamp >= %s
               AND submission_timestamp < %s
             GROUP BY DATE(submission_timestamp)
             ORDER BY demand_date
            """,
            (history_start, history_end + timedelta(days=1)),
        ).fetchall()
        history_by_date = {
            row["demand_date"]: int(row["demand_count"])
            for row in rows
        }

        history_series = []
        current_date = start_date
        while current_date <= display_end_date:
            history_series.append({
                "date": current_date.isoformat(),
                "value": history_by_date.get(self._previous_year_date(current_date), 0),
            })
            current_date += timedelta(days=1)

        return history_series

    def _project_value(
        self,
        forecast_date: date,
        weekday_totals: dict[int, list[float]],
        month_totals: dict[int, list[float]],
        baseline: float,
        recent_baseline: float,
    ) -> float:
        weekday_average = average(weekday_totals.get(forecast_date.weekday(), []), baseline)
        month_average = average(month_totals.get(forecast_date.month, []), baseline)
        seasonal_boost = 1.0
        if forecast_date.month in (5, 6):
            seasonal_boost = 1.25
        elif forecast_date.month in (1, 2):
            seasonal_boost = 0.9

        trend_adjustment = ((recent_baseline - baseline) / max(1.0, baseline)) * 0.35 if baseline > 0 else 0.0
        return round(
            max(
                0.0,
                (weekday_average * 0.35 + month_average * 0.45 + recent_baseline * 0.20)
                * seasonal_boost
                * (1 + trend_adjustment),
            ),
            2,
        )

    def _previous_year_date(self, value: date) -> date:
        try:
            return value.replace(year=value.year - 1)
        except ValueError:
            return value.replace(year=value.year - 1, day=28)
