from typing import Any

from psycopg import Connection


class ReadinessSnapshotBuilder:
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
            is_inactive = (
                equipment_state.lower() in {"under maintenance", "unavailable"}
                or operational_status.lower() in {"under maintenance", "unavailable"}
            )
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
