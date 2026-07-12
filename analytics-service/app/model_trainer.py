from datetime import UTC, date, datetime, time, timedelta
import math
from typing import Any

from psycopg import Connection
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
from sklearn.metrics import accuracy_score, confusion_matrix, f1_score, mean_absolute_error, precision_score, recall_score
from sklearn.model_selection import train_test_split
from statsmodels.tsa.statespace.sarimax import SARIMAX

from app.model_artifacts import (
    ALLOCATION_ARTIFACT,
    FORECAST_ARTIFACT,
    READINESS_ARTIFACT,
    ModelArtifactStore,
)


class AnalyticsModelTrainer:
    def __init__(self, artifact_store: ModelArtifactStore | None = None) -> None:
        self.artifact_store = artifact_store or ModelArtifactStore()

    def train_all(
        self,
        connection: Connection,
        triggered_by: str = "scheduler",
        set_name: str | None = None,
        activate: bool = True,
    ) -> dict[str, Any]:
        trained_at = datetime.now(UTC)
        model_set = set_name or trained_at.strftime("weekly-%Y%m%d-%H%M%S")
        results = {
            "forecast": self._train_forecast(connection, trained_at, model_set),
            "readiness": self._train_readiness(connection, trained_at, model_set),
            "allocation": self._train_allocation(connection, trained_at, model_set),
        }
        if activate and any(result.get("status") == "trained" for result in results.values()):
            self.artifact_store.activate_set(model_set)
        return {
            "status": "completed",
            "triggeredBy": triggered_by,
            "setName": model_set,
            "activeSet": self.artifact_store.active_set(),
            "trainedAt": trained_at.isoformat(),
            "artifacts": results,
        }

    def _train_forecast(self, connection: Connection, trained_at: datetime, model_set: str) -> dict[str, Any]:
        rows = connection.execute(
            """
            SELECT DATE(event_date_time) AS demand_date,
                   COUNT(*)::int AS demand_count
              FROM reservations
             WHERE event_date_time IS NOT NULL
             GROUP BY DATE(event_date_time)
             ORDER BY demand_date
            """
        ).fetchall()
        if not rows:
            return self._skipped(FORECAST_ARTIFACT, "No scheduled reservation usage history is available.")

        counts_by_date = {row["demand_date"]: int(row["demand_count"]) for row in rows}
        start_date = min(counts_by_date)
        end_date = max(counts_by_date)
        feature_rows: list[list[float]] = []
        target_values: list[float] = []
        historical_counts: dict[str, int] = {}
        current_date = start_date
        while current_date <= end_date:
            target = counts_by_date.get(current_date, 0)
            feature_rows.append(self._forecast_features(current_date, start_date, counts_by_date))
            target_values.append(float(target))
            historical_counts[current_date.isoformat()] = target
            current_date += timedelta(days=1)

        if len(target_values) < 14:
            return self._skipped(FORECAST_ARTIFACT, "At least fourteen calendar days are needed to train SARIMA forecasting.")

        score: float | None = None
        if len(target_values) >= 28 and len(set(target_values)) > 1:
            test_size = max(3, int(len(target_values) * 0.2))
            train_y = target_values[:-test_size]
            test_y = target_values[-test_size:]
            validation_metrics = None
            try:
                validation_model = self._fit_sarima(train_y)
                predictions = [float(value) for value in validation_model.get_forecast(steps=test_size).predicted_mean]
                score = round(float(mean_absolute_error(test_y, predictions)), 4)
                validation_metrics = self._forecast_validation_metrics(train_y, test_y, predictions)
            except Exception:
                score = None
                validation_metrics = None
        else:
            validation_metrics = None

        try:
            model = self._fit_sarima(target_values)
        except Exception as error:
            return self._skipped(FORECAST_ARTIFACT, f"SARIMA training did not converge: {error}")

        artifact = {
            "model": model,
            "modelType": "sarima",
            "startDate": start_date.isoformat(),
            "endDate": end_date.isoformat(),
            "historicalCounts": historical_counts,
            "featureVersion": 1,
            "metadata": {
                "modelName": "weekly_sarima_forecast",
                "modelFamily": "SARIMA",
                "trainedAt": trained_at.isoformat(),
                "trainingRows": len(target_values),
                "score": score,
                "scoreName": "mean_absolute_error",
                "validationMetrics": validation_metrics,
            },
        }
        path = self.artifact_store.save(FORECAST_ARTIFACT, artifact, model_set)
        return self._saved(FORECAST_ARTIFACT, path, artifact["metadata"])

    def _train_readiness(self, connection: Connection, trained_at: datetime, model_set: str) -> dict[str, Any]:
        equipment_rows = connection.execute(
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
        if not equipment_rows:
            return self._skipped(READINESS_ARTIFACT, "No equipment records are available.")

        usage_map = self._equipment_usage_map(connection, date.today() - timedelta(days=90), date.today())
        feature_rows: list[list[float]] = []
        labels: list[str] = []
        for row in equipment_rows:
            features = self._readiness_features(row, usage_map)
            label = self._readiness_label_from_features(features)
            feature_rows.append(features)
            labels.append(label)

        if len(feature_rows) < 3 or len(set(labels)) < 2:
            return self._skipped(READINESS_ARTIFACT, "Readiness training needs at least two risk bands.")

        model = RandomForestClassifier(
            n_estimators=160,
            random_state=42,
            class_weight="balanced",
            min_samples_leaf=1,
        )
        score: float | None = None
        if len(feature_rows) >= max(10, len(set(labels)) * 4):
            train_x, test_x, train_y, test_y = train_test_split(
                feature_rows,
                labels,
                test_size=0.25,
                random_state=42,
                stratify=labels if min(labels.count(label) for label in set(labels)) >= 2 else None,
            )
            model.fit(train_x, train_y)
            predicted_y = model.predict(test_x)
            score = round(float(accuracy_score(test_y, predicted_y)), 4)
            validation_metrics = self._classification_validation_metrics(test_y, predicted_y, list(model.classes_))
        else:
            model.fit(feature_rows, labels)
            predicted_y = model.predict(feature_rows)
            validation_metrics = self._classification_validation_metrics(labels, predicted_y, list(model.classes_))

        artifact = {
            "model": model,
            "usageWindowDays": 90,
            "featureVersion": 1,
            "metadata": {
                "modelName": "weekly_random_forest_readiness",
                "trainedAt": trained_at.isoformat(),
                "trainingRows": len(feature_rows),
                "score": score,
                "scoreName": "accuracy",
                "validationMetrics": validation_metrics,
            },
        }
        path = self.artifact_store.save(READINESS_ARTIFACT, artifact, model_set)
        return self._saved(READINESS_ARTIFACT, path, artifact["metadata"])

    def _train_allocation(self, connection: Connection, trained_at: datetime, model_set: str) -> dict[str, Any]:
        usage_map = self._equipment_usage_map(connection, date.today() - timedelta(days=365), date.today())
        equipment_rows = connection.execute(
            """
            SELECT equipment_name,
                   equipment_category,
                   total_quantity,
                   available_quantity,
                   operational_status,
                   equipment_state
              FROM equipment
             ORDER BY equipment_name
            """
        ).fetchall()
        priority_rows = connection.execute(
            """
            SELECT COALESCE(priority_level, 'Normal') AS priority_level,
                   COUNT(*)::int AS request_count
              FROM reservations
             WHERE event_date_time >= %s
               AND LOWER(COALESCE(current_status, '')) NOT IN ('cancelled', 'rejected')
             GROUP BY COALESCE(priority_level, 'Normal')
            """,
            (datetime.combine(date.today() - timedelta(days=365), time.min),),
        ).fetchall()
        if not usage_map and not priority_rows:
            return self._skipped(ALLOCATION_ARTIFACT, "No reservation history is available for allocation profiling.")

        max_usage = max(usage_map.values(), default=1)
        equipment_weights = {
            equipment_name: round(1 + (usage_count / max(1, max_usage)), 4)
            for equipment_name, usage_count in usage_map.items()
        }
        max_priority_count = max([int(row["request_count"]) for row in priority_rows], default=1)
        priority_weights = {
            str(row["priority_level"] or "Normal").lower(): round(1 + (int(row["request_count"]) / max(1, max_priority_count)), 4)
            for row in priority_rows
        }
        utilization_features = [
            self._utilization_features(row, usage_map)
            for row in equipment_rows
        ]
        utilization_targets = [
            float(usage_map.get(str(row["equipment_name"] or "").lower(), 0))
            for row in equipment_rows
        ]
        utilization_model = None
        utilization_score: float | None = None
        if utilization_features and len(set(utilization_targets)) > 1:
            utilization_model = RandomForestRegressor(
                n_estimators=160,
                random_state=42,
                min_samples_leaf=1,
            )
            if len(utilization_features) >= 10:
                train_x, test_x, train_y, test_y = train_test_split(
                    utilization_features,
                    utilization_targets,
                    test_size=0.25,
                    random_state=42,
                )
                utilization_model.fit(train_x, train_y)
                utilization_score = round(float(mean_absolute_error(test_y, utilization_model.predict(test_x))), 4)
            else:
                utilization_model.fit(utilization_features, utilization_targets)

        artifact = {
            "equipmentWeights": equipment_weights,
            "priorityWeights": priority_weights,
            "utilizationModel": utilization_model,
            "utilizationFeatureVersion": 1,
            "metadata": {
                "modelName": "weekly_bilp_allocation",
                "modelFamily": "Binary Integer Linear Programming",
                "utilizationModelName": "weekly_random_forest_utilization",
                "utilizationModelFamily": "Random Forest",
                "trainedAt": trained_at.isoformat(),
                "trainingRows": sum(usage_map.values()),
                "score": None,
                "scoreName": "historical_usage_profile",
                "utilizationScore": utilization_score,
                "utilizationScoreName": "mean_absolute_error",
            },
        }
        path = self.artifact_store.save(ALLOCATION_ARTIFACT, artifact, model_set)
        return self._saved(ALLOCATION_ARTIFACT, path, artifact["metadata"])

    def _fit_sarima(self, values: list[float]):
        return SARIMAX(
            values,
            order=(1, 0, 1),
            seasonal_order=(1, 0, 1, 7),
            enforce_stationarity=False,
            enforce_invertibility=False,
        ).fit(method="powell", disp=False, maxiter=120)

    def _forecast_validation_metrics(
        self,
        train_y: list[float],
        test_y: list[float],
        predictions: list[float],
    ) -> dict[str, Any]:
        sarima_mape = self._mape(test_y, predictions)
        naive_predictions = self._naive_predictions(train_y, test_y, lag=1)
        seasonal_naive_predictions = self._naive_predictions(train_y, test_y, lag=7)
        naive_mape = self._mape(test_y, naive_predictions)
        seasonal_naive_mape = self._mape(test_y, seasonal_naive_predictions)
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
            "zeroDemandExcluded": sum(1 for value in test_y if float(value) == 0),
        }

    def _naive_predictions(self, train_y: list[float], test_y: list[float], lag: int) -> list[float]:
        history = [float(value) for value in train_y]
        predictions = []
        for actual in test_y:
            if len(history) >= lag:
                predictions.append(history[-lag])
            else:
                predictions.append(history[-1] if history else 0.0)
            history.append(float(actual))
        return predictions

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

    def _classification_validation_metrics(
        self,
        actual_labels: list[str],
        predicted_labels: Any,
        labels: list[str],
    ) -> dict[str, Any]:
        predicted = [str(value) for value in predicted_labels]
        actual = [str(value) for value in actual_labels]
        label_order = [str(label) for label in labels]
        matrix = confusion_matrix(actual, predicted, labels=label_order)
        return {
            "accuracy": round(float(accuracy_score(actual, predicted)), 4),
            "precision": round(float(precision_score(actual, predicted, labels=label_order, average="weighted", zero_division=0)), 4),
            "recall": round(float(recall_score(actual, predicted, labels=label_order, average="weighted", zero_division=0)), 4),
            "f1": round(float(f1_score(actual, predicted, labels=label_order, average="weighted", zero_division=0)), 4),
            "labels": label_order,
            "confusionMatrix": matrix.astype(int).tolist(),
        }

    def _forecast_features(self, target_date: date, start_date: date, counts_by_date: dict[date, int]) -> list[float]:
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

    def _utilization_features(self, row: Any, usage_map: dict[str, int]) -> list[float]:
        total_quantity = max(0, int(row["total_quantity"] or 0))
        available_quantity = max(0, int(row["available_quantity"] or 0))
        availability_ratio = available_quantity / total_quantity if total_quantity > 0 else 1
        equipment_state = str(row["equipment_state"] or "").lower()
        operational_status = str(row["operational_status"] or "").lower()
        is_inactive = (
            equipment_state in {"under maintenance", "unavailable"}
            or operational_status in {"under maintenance", "unavailable"}
        )
        usage_count = usage_map.get(str(row["equipment_name"] or "").lower(), 0)
        category_key = str(row["equipment_category"] or "Others").lower()
        category_bucket = sum(ord(character) for character in category_key) % 17
        return [
            float(total_quantity),
            float(available_quantity),
            float(availability_ratio),
            1.0 if is_inactive else 0.0,
            float(usage_count),
            float(category_bucket),
        ]

    def _readiness_features(self, row: Any, usage_map: dict[str, int]) -> list[float]:
        total_quantity = max(0, int(row["total_quantity"] or 0))
        available_quantity = max(0, int(row["available_quantity"] or 0))
        availability_ratio = available_quantity / total_quantity if total_quantity > 0 else 1
        state = str(row["equipment_state"] or "").lower()
        operational_status = str(row["operational_status"] or "").lower()
        inactive = 1.0 if state in {"under maintenance", "unavailable"} or operational_status in {"under maintenance", "unavailable"} else 0.0
        usage_count = usage_map.get(str(row["equipment_name"] or "").lower(), 0)
        return [
            float(total_quantity),
            float(available_quantity),
            float(availability_ratio),
            inactive,
            float(usage_count),
        ]

    def _readiness_label_from_features(self, features: list[float]) -> str:
        availability_ratio = features[2]
        inactive = features[3] >= 1
        usage_count = features[4]
        score = 0
        if availability_ratio <= 0.2:
            score += 3
        elif availability_ratio <= 0.45:
            score += 4
        elif availability_ratio <= 0.7:
            score += 2
        if inactive:
            score += 3
        if usage_count >= 20:
            score += 2
        elif usage_count >= 10:
            score += 1
        if score >= 6:
            return "High Risk"
        if score >= 4:
            return "Medium Risk"
        if score >= 2:
            return "Low Risk"
        return "Very Low Risk"

    def _equipment_usage_map(self, connection: Connection, start_date: date, end_date: date) -> dict[str, int]:
        rows = connection.execute(
            """
            SELECT requested_equipment_list
              FROM reservations
             WHERE event_date_time >= %s
               AND event_date_time <= %s
               AND LOWER(COALESCE(current_status, '')) NOT IN ('cancelled', 'rejected')
            """,
            (datetime.combine(start_date, time.min), datetime.combine(end_date, time.max)),
        ).fetchall()

        usage: dict[str, int] = {}
        for row in rows:
            requested_items = row["requested_equipment_list"] or []
            if isinstance(requested_items, str):
                import json

                requested_items = json.loads(requested_items)
            for item in requested_items:
                equipment_name = str(item.get("equipmentName") or item.get("name") or "").strip().lower()
                if not equipment_name:
                    continue
                usage[equipment_name] = usage.get(equipment_name, 0) + max(1, int(item.get("quantity") or 1))
        return usage

    def _saved(self, filename: str, path: Any, metadata: dict[str, Any]) -> dict[str, Any]:
        return {
            "status": "trained",
            "artifact": filename,
            "path": str(path),
            "metadata": metadata,
        }

    def _skipped(self, filename: str, reason: str) -> dict[str, Any]:
        return {
            "status": "skipped",
            "artifact": filename,
            "reason": reason,
        }
