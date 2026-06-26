from datetime import date, datetime, time, timedelta
import json
from typing import Any

from psycopg import Connection

from app.model_artifacts import READINESS_ARTIFACT, ModelArtifactStore


class ReadinessSnapshotBuilder:
    def __init__(self, artifact_store: ModelArtifactStore | None = None) -> None:
        self.artifact_store = artifact_store or ModelArtifactStore()

    def build(self, connection: Connection) -> dict[str, Any]:
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
        trained_artifact = self.artifact_store.load(READINESS_ARTIFACT)
        trained_model = trained_artifact.get("model") if trained_artifact is not None else None
        usage_window_days = int(trained_artifact.get("usageWindowDays", 90)) if trained_artifact is not None else 90
        usage_map = self._build_usage_map(connection, usage_window_days) if trained_model is not None else {}

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
            equipment_state = str(row["equipment_state"] or "")
            operational_status = str(row["operational_status"] or "")
            is_inactive = (
                equipment_state.lower() in {"under maintenance", "unavailable"}
                or operational_status.lower() in {"under maintenance", "unavailable"}
            )
            usage_count = usage_map.get(str(row["equipment_name"] or "").lower(), 0)
            score = self._rule_score(availability_ratio, is_inactive, usage_count)
            self._count_factors(factor_counts, availability_ratio, is_inactive, usage_count)
            if trained_model is not None:
                band_label = str(trained_model.predict([self._features(row, usage_map)])[0])
            else:
                band_label = self._band_from_score(score)
            risk_label = "ready" if band_label in {"Low Risk", "Very Low Risk"} and not is_inactive else "watch"

            if band_label == "High Risk":
                high_risk_equipment.append(
                    {
                        "name": row["equipment_name"],
                        "score": score,
                        "usageCount": usage_count,
                    }
                )

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
            "modelName": self._artifact_model_name(trained_artifact, "random_forest"),
            "status": "trained_weekly" if trained_model is not None else "placeholder_ready",
            "records": records,
            "bands": ordered_bands,
            "topRiskFactors": [factor for factor, _count in sorted_factors[:4]],
            "highRiskEquipment": high_risk_equipment[:5],
            "safeRate": round((safe_equipment / total_equipment) * 100, 1) if total_equipment > 0 else 0,
            "riskSummary": {
                "readyCount": sum(1 for item in records if item["readinessLabel"] == "ready"),
                "watchCount": sum(1 for item in records if item["readinessLabel"] == "watch"),
            },
            "modelStatus": self.artifact_store.describe(READINESS_ARTIFACT),
            "notes": (
                "Weekly trained .pkl readiness artifact is active."
                if trained_model is not None
                else "FastAPI storage path is live. Weekly training will replace this placeholder output."
            ),
        }

    def _features(self, row: Any, usage_map: dict[str, int]) -> list[float]:
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
        return [
            float(total_quantity),
            float(available_quantity),
            float(availability_ratio),
            1.0 if is_inactive else 0.0,
            float(usage_count),
        ]

    def _rule_score(self, availability_ratio: float, is_inactive: bool, usage_count: int) -> int:
        score = 0
        if availability_ratio <= 0.2:
            score += 3
        elif availability_ratio <= 0.45:
            score += 4
        elif availability_ratio <= 0.7:
            score += 2
        if is_inactive:
            score += 3
        if usage_count >= 20:
            score += 2
        elif usage_count >= 10:
            score += 1
        return score

    def _band_from_score(self, score: int) -> str:
        if score >= 6:
            return "High Risk"
        if score >= 4:
            return "Medium Risk"
        if score >= 2:
            return "Low Risk"
        return "Very Low Risk"

    def _count_factors(
        self,
        factor_counts: dict[str, int],
        availability_ratio: float,
        is_inactive: bool,
        usage_count: int,
    ) -> None:
        if availability_ratio <= 0.45:
            factor_counts["Low stock pressure"] += 1
        if is_inactive:
            factor_counts["Inactive availability state"] += 1
        if usage_count >= 10:
            factor_counts["High usage frequency"] += 1

    def _build_usage_map(self, connection: Connection, usage_window_days: int) -> dict[str, int]:
        end_date = date.today()
        start_date = end_date - timedelta(days=max(1, usage_window_days))
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
                requested_items = json.loads(requested_items)
            for item in requested_items:
                equipment_name = str(item.get("equipmentName") or item.get("name") or "").strip().lower()
                if not equipment_name:
                    continue
                usage[equipment_name] = usage.get(equipment_name, 0) + max(1, int(item.get("quantity") or 1))
        return usage

    def _artifact_model_name(self, artifact: dict[str, Any] | None, fallback: str) -> str:
        if artifact is None:
            return fallback
        metadata = artifact.get("metadata", {})
        return str(metadata.get("modelName") or fallback)
