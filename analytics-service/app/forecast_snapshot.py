from datetime import date, timedelta
import math
from typing import Any

from psycopg import Connection

from app.analytics_utils import average
from app.model_artifacts import FORECAST_ARTIFACT, ModelArtifactStore


class ForecastSnapshotBuilder:
    def __init__(self, artifact_store: ModelArtifactStore | None = None) -> None:
        self.artifact_store = artifact_store or ModelArtifactStore()

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

        trained_artifact = self.artifact_store.load(FORECAST_ARTIFACT)
        if trained_artifact is not None and trained_artifact.get("model") is None:
            trained_artifact = None
        forecast_series = (
            self._build_trained_forecast_series(series, forecast_days, trained_artifact)
            if trained_artifact is not None
            else self._build_forecast_series(series, forecast_days)
        )
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
        accuracy_metrics = self._build_accuracy_metrics(series, forecast_series)

        return {
            "modelName": self._artifact_model_name(trained_artifact, "sarima"),
            "status": "trained_weekly" if trained_artifact is not None else "placeholder_ready",
            "actualSeries": series,
            "forecastSeries": forecast_series,
            "historySeries": history_series,
            "forecastPeak": forecast_peak,
            "accuracyMetrics": accuracy_metrics,
            "summary": {
                "averageActualDemand": round(average_actual, 2),
                "averageForecastDemand": round(average_forecast, 2),
                "expectedChangePercent": expected_change_percent,
                "forecastHorizonDays": forecast_days,
                "sarimaMape": accuracy_metrics["sarimaMape"],
                "naiveMape": accuracy_metrics["naiveMape"],
                "seasonalNaiveMape": accuracy_metrics["seasonalNaiveMape"],
                "forecastImprovementPercent": accuracy_metrics["forecastImprovementPercent"],
                "accuracyStatus": accuracy_metrics["accuracyStatus"],
            },
            "modelStatus": self.artifact_store.describe(FORECAST_ARTIFACT),
            "notes": (
                "Weekly trained .pkl forecast artifact is active."
                if trained_artifact is not None
                else "FastAPI storage path is live. Weekly training will replace this placeholder output."
            ),
        }

    def _build_trained_forecast_series(
        self,
        actual_series: list[dict[str, Any]],
        forecast_days: int,
        artifact: dict[str, Any],
    ) -> list[dict[str, Any]]:
        if not actual_series:
            return []

        if artifact.get("modelType") == "sarima":
            sarima_series = self._build_sarima_forecast_series(actual_series, forecast_days, artifact)
            if sarima_series:
                return sarima_series

        model = artifact.get("model")
        model_start_date = date.fromisoformat(artifact.get("startDate"))
        historical_counts = {
            date.fromisoformat(day): int(count)
            for day, count in dict(artifact.get("historicalCounts", {})).items()
        }
        selected_counts = {
            date.fromisoformat(item["date"]): int(float(item["value"]))
            for item in actual_series
        }
        counts_by_date = {**historical_counts, **selected_counts}
        sorted_series = sorted(actual_series, key=lambda item: item["date"])
        first_date = date.fromisoformat(sorted_series[0]["date"])
        total_days = len(sorted_series) + max(0, forecast_days)
        forecast_rows = []

        for offset in range(total_days):
            forecast_date = first_date + timedelta(days=offset)
            features = self._trained_forecast_features(forecast_date, model_start_date, counts_by_date)
            predicted_value = float(model.predict([features])[0])
            forecast_rows.append({"date": forecast_date.isoformat(), "value": round(max(0.0, predicted_value), 2)})

        return forecast_rows

    def _build_sarima_forecast_series(
        self,
        actual_series: list[dict[str, Any]],
        forecast_days: int,
        artifact: dict[str, Any],
    ) -> list[dict[str, Any]]:
        model = artifact.get("model")
        if model is None:
            return []

        model_start_date = date.fromisoformat(artifact.get("startDate"))
        sorted_series = sorted(actual_series, key=lambda item: item["date"])
        first_date = date.fromisoformat(sorted_series[0]["date"])
        start_offset = (first_date - model_start_date).days
        if start_offset < 0:
            return []

        total_days = len(sorted_series) + max(0, forecast_days)
        end_offset = start_offset + total_days - 1
        try:
            predictions = model.get_prediction(start=start_offset, end=end_offset).predicted_mean
        except Exception:
            return []

        forecast_rows = []
        for offset, predicted_value in enumerate(predictions):
            forecast_date = first_date + timedelta(days=offset)
            forecast_rows.append({"date": forecast_date.isoformat(), "value": round(max(0.0, float(predicted_value)), 2)})

        return forecast_rows

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

    def _build_accuracy_metrics(
        self,
        actual_series: list[dict[str, Any]],
        forecast_series: list[dict[str, Any]],
    ) -> dict[str, Any]:
        actual_by_date = {
            item["date"]: float(item["value"])
            for item in actual_series
        }
        forecast_by_date = {
            item["date"]: float(item["value"])
            for item in forecast_series
        }
        ordered_dates = sorted(actual_by_date)
        actual_values = [actual_by_date[day] for day in ordered_dates]
        sarima_predictions = [forecast_by_date.get(day, 0.0) for day in ordered_dates]
        naive_predictions = []
        seasonal_naive_predictions = []

        for index, actual_value in enumerate(actual_values):
            naive_predictions.append(actual_values[index - 1] if index >= 1 else actual_value)
            seasonal_naive_predictions.append(actual_values[index - 7] if index >= 7 else naive_predictions[-1])

        sarima_mape = self._mape(actual_values, sarima_predictions)
        naive_mape = self._mape(actual_values, naive_predictions)
        seasonal_naive_mape = self._mape(actual_values, seasonal_naive_predictions)
        benchmark_mape = seasonal_naive_mape if seasonal_naive_mape is not None else naive_mape
        improvement_percent = None
        if benchmark_mape is not None and benchmark_mape > 0 and sarima_mape is not None:
            improvement_percent = round(((benchmark_mape - sarima_mape) / benchmark_mape) * 100, 2)

        return {
            "sarimaMape": sarima_mape,
            "naiveMape": naive_mape,
            "seasonalNaiveMape": seasonal_naive_mape,
            "forecastImprovementPercent": improvement_percent,
            "accuracyStatus": self._mape_status(sarima_mape),
            "benchmarkMethod": "seasonal_naive" if seasonal_naive_mape is not None else "naive",
            "zeroDemandExcluded": sum(1 for value in actual_values if value == 0),
            "evaluatedPeriods": sum(1 for value in actual_values if value != 0),
            "evaluationStartDate": ordered_dates[0] if ordered_dates else None,
            "evaluationEndDate": ordered_dates[-1] if ordered_dates else None,
        }

    def _mape(self, actual_values: list[float], predicted_values: list[float]) -> float | None:
        errors = [
            abs((float(actual) - float(predicted)) / float(actual))
            for actual, predicted in zip(actual_values, predicted_values)
            if float(actual) != 0
        ]
        if not errors:
            return None
        return round((sum(errors) / len(errors)) * 100, 2)

    def _mape_status(self, value: float | None) -> str:
        if value is None:
            return "insufficient_data"
        return "good" if value <= 20 else "needs_review"

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

    def _trained_forecast_features(
        self,
        target_date: date,
        start_date: date,
        counts_by_date: dict[date, int],
    ) -> list[float]:
        day_index = (target_date - start_date).days
        previous_7 = [
            counts_by_date.get(target_date - timedelta(days=offset), 0)
            for offset in range(1, 8)
        ]
        previous_14 = [
            counts_by_date.get(target_date - timedelta(days=offset), 0)
            for offset in range(1, 15)
        ]
        return [
            float(day_index),
            float(target_date.weekday()),
            float(target_date.month),
            math.sin((2 * math.pi * target_date.weekday()) / 7),
            math.cos((2 * math.pi * target_date.weekday()) / 7),
            math.sin((2 * math.pi * target_date.month) / 12),
            math.cos((2 * math.pi * target_date.month) / 12),
            sum(previous_7) / 7,
            sum(previous_14) / 14,
            1.0 if target_date.month in (5, 6) else 0.0,
            1.0 if target_date.month in (1, 2) else 0.0,
        ]

    def _artifact_model_name(self, artifact: dict[str, Any] | None, fallback: str) -> str:
        if artifact is None:
            return fallback
        metadata = artifact.get("metadata", {})
        return str(metadata.get("modelName") or fallback)

    def _previous_year_date(self, value: date) -> date:
        try:
            return value.replace(year=value.year - 1)
        except ValueError:
            return value.replace(year=value.year - 1, day=28)
