from datetime import date, datetime, time, timedelta
import json
from typing import Any

from psycopg import Connection
try:
    import pulp
except Exception:
    pulp = None

from app.model_artifacts import ALLOCATION_ARTIFACT, ModelArtifactStore


class AllocationSnapshotBuilder:
    def __init__(self, artifact_store: ModelArtifactStore | None = None) -> None:
        self.artifact_store = artifact_store or ModelArtifactStore()
        self._last_optimizer_status = "not_run"

    def build(self, connection: Connection, config: dict[str, Any]) -> dict[str, Any]:
        range_days = int(config.get("allocation", {}).get("historyDays", 30))
        configured_range = config.get("dateRange", {})
        end_date = date.fromisoformat(configured_range["endDate"]) if configured_range.get("endDate") else date.today()
        start_date = date.fromisoformat(configured_range["startDate"]) if configured_range.get("startDate") else (
            end_date - timedelta(days=max(0, range_days - 1))
        )
        previous_year_start = start_date.replace(year=start_date.year - 1)
        previous_year_end = end_date.replace(year=end_date.year - 1)

        pending_rows = self._fetch_pending_reservations(connection)
        equipment_rows = self._fetch_equipment(connection)
        trained_artifact = self.artifact_store.load(ALLOCATION_ARTIFACT)
        allocation_plan = self._build_allocation_plan(pending_rows, equipment_rows, trained_artifact)

        current_usage_map = self._build_equipment_usage_map(connection, start_date, end_date)
        today_usage_map = self._build_equipment_usage_map(connection, date.today(), date.today())
        previous_year_usage_map = self._build_equipment_usage_map(connection, previous_year_start, previous_year_end)
        utilization_by_category = self._build_utilization_by_category(equipment_rows, current_usage_map, trained_artifact)
        utilization_comparison_by_category = self._build_utilization_by_category(equipment_rows, previous_year_usage_map)
        equipment_trend_config = self._resolve_equipment_trend_config(config)
        all_top_equipment = self._build_top_equipment(equipment_rows, current_usage_map, today_usage_map)
        all_possible_borrowed_equipment = self._build_possible_borrowed_equipment(
            equipment_rows,
            current_usage_map,
            previous_year_usage_map,
        )
        top_equipment_page = self._paginate_items(
            all_top_equipment,
            equipment_trend_config["topEquipmentPage"],
            equipment_trend_config["pageSize"],
            equipment_trend_config["maxPages"],
        )
        possible_borrowed_equipment_page = self._paginate_items(
            all_possible_borrowed_equipment,
            equipment_trend_config["preparationDecisionPage"],
            equipment_trend_config["pageSize"],
            equipment_trend_config["maxPages"],
        )

        return {
            "modelName": self._artifact_model_name(trained_artifact, "bilp"),
            "utilizationModelName": self._artifact_utilization_model_name(trained_artifact, "random_forest"),
            "optimizerStatus": self._last_optimizer_status,
            "status": "trained_weekly" if trained_artifact is not None else "placeholder_ready",
            "dateRange": {
                "startDate": start_date.isoformat(),
                "endDate": end_date.isoformat(),
                "comparisonStartDate": previous_year_start.isoformat(),
                "comparisonEndDate": previous_year_end.isoformat(),
            },
            "pendingRequestCount": len(pending_rows),
            "allocationPlan": allocation_plan,
            "utilizationByCategory": utilization_by_category,
            "utilizationComparisonByCategory": utilization_comparison_by_category,
            "topEquipment": top_equipment_page["items"],
            "possibleBorrowedEquipment": possible_borrowed_equipment_page["items"],
            "equipmentTrendPagination": {
                "topEquipment": top_equipment_page["pagination"],
                "preparationDecisions": possible_borrowed_equipment_page["pagination"],
            },
            "summary": {
                "totalEquipment": len(equipment_rows),
                "activeReservations": len(allocation_plan),
                "pendingRequests": len(pending_rows),
                "completedThisPeriod": self._count_completed_reservations(connection, start_date, end_date),
            },
            "fulfilledCount": sum(1 for item in allocation_plan if item["status"] == "allocated"),
            "partialCount": sum(1 for item in allocation_plan if item["status"] == "partial"),
            "modelStatus": self.artifact_store.describe(ALLOCATION_ARTIFACT),
            "notes": self._build_notes(trained_artifact),
        }

    def _fetch_pending_reservations(self, connection: Connection) -> list[Any]:
        return connection.execute(
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
             ORDER BY event_date_time ASC, submission_timestamp ASC
            """
        ).fetchall()

    def _fetch_equipment(self, connection: Connection) -> list[Any]:
        return connection.execute(
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

    def _build_allocation_plan(
        self,
        pending_rows: list[Any],
        equipment_rows: list[Any],
        artifact: dict[str, Any] | None = None,
    ) -> list[dict[str, Any]]:
        bilp_plan = self._build_bilp_allocation_plan(pending_rows, equipment_rows, artifact)
        if bilp_plan is not None:
            return bilp_plan

        self._last_optimizer_status = "greedy_fallback"
        allocation_plan: list[dict[str, Any]] = []
        equipment_weights = dict(artifact.get("equipmentWeights", {})) if artifact else {}
        priority_weights = dict(artifact.get("priorityWeights", {})) if artifact else {}
        ordered_pending_rows = sorted(
            pending_rows,
            key=lambda request: (
                -float(priority_weights.get(str(request["priority_level"] or "Normal").lower(), 1.0)),
                request["event_date_time"],
                request["submission_timestamp"],
            ),
        )
        ordered_equipment_rows = sorted(
            equipment_rows,
            key=lambda equipment: (
                -float(equipment_weights.get(str(equipment["equipment_name"] or "").lower(), 1.0)),
                -max(0, int(equipment["available_quantity"] or 0)),
                str(equipment["equipment_name"] or ""),
            ),
        )
        remaining_capacity = {
            int(row["equipment_identifier"]): max(0, int(row["available_quantity"] or 0))
            for row in ordered_equipment_rows
        }

        for request in ordered_pending_rows:
            line_items: list[dict[str, Any]] = []
            allocated_total = 0
            requested_items = self._parse_requested_items(request["requested_equipment_list"])
            for item in requested_items:
                item_name = str(item.get("equipmentName") or item.get("name") or "Unknown")
                item_quantity = max(0, int(item.get("quantity") or 0))
                allocated_quantity = 0

                for equipment in ordered_equipment_rows:
                    equipment_identifier = int(equipment["equipment_identifier"])
                    if remaining_capacity[equipment_identifier] <= 0:
                        continue
                    if str(equipment["equipment_name"]).lower() != item_name.lower():
                        continue
                    allocated_quantity = min(item_quantity, remaining_capacity[equipment_identifier])
                    remaining_capacity[equipment_identifier] -= allocated_quantity
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

        return allocation_plan

    def _build_bilp_allocation_plan(
        self,
        pending_rows: list[Any],
        equipment_rows: list[Any],
        artifact: dict[str, Any] | None = None,
    ) -> list[dict[str, Any]] | None:
        if pulp is None:
            return None
        if not pending_rows:
            self._last_optimizer_status = "bilp_no_pending_requests"
            return []

        equipment_weights = dict(artifact.get("equipmentWeights", {})) if artifact else {}
        priority_weights = dict(artifact.get("priorityWeights", {})) if artifact else {}
        active_equipment = [
            row for row in equipment_rows
            if max(0, int(row["available_quantity"] or 0)) > 0
            and str(row["equipment_state"] or "").lower() not in {"under maintenance", "unavailable"}
            and str(row["operational_status"] or "").lower() not in {"under maintenance", "unavailable"}
        ]
        equipment_by_id = {
            int(row["equipment_identifier"]): row
            for row in active_equipment
        }
        request_lines = []
        request_line_count: dict[int, int] = {}
        for request_index, request in enumerate(pending_rows):
            for line_index, item in enumerate(self._parse_requested_items(request["requested_equipment_list"])):
                item_name = str(item.get("equipmentName") or item.get("name") or "Unknown")
                item_quantity = max(0, int(item.get("quantity") or 0))
                if item_quantity <= 0:
                    continue
                request_lines.append(
                    {
                        "requestIndex": request_index,
                        "lineIndex": line_index,
                        "equipmentName": item_name,
                        "quantity": item_quantity,
                    }
                )
                request_line_count[request_index] = request_line_count.get(request_index, 0) + 1

        if not request_lines:
            self._last_optimizer_status = "bilp_no_requested_equipment"
            return self._empty_allocation_plan(pending_rows)

        problem = pulp.LpProblem("techreserve_bilp_allocation", pulp.LpMaximize)
        assignments: dict[tuple[int, int, int], Any] = {}
        fulfilled_vars: dict[int, Any] = {
            request_index: pulp.LpVariable(f"u_{request_index}", cat="Binary")
            for request_index in range(len(pending_rows))
        }

        for line in request_lines:
            for equipment_id, equipment in equipment_by_id.items():
                if str(equipment["equipment_name"]).lower() != line["equipmentName"].lower():
                    continue
                assignments[(line["requestIndex"], line["lineIndex"], equipment_id)] = pulp.LpVariable(
                    f"x_{line['requestIndex']}_{line['lineIndex']}_{equipment_id}",
                    cat="Binary",
                )

        if not assignments:
            self._last_optimizer_status = "bilp_no_feasible_matches"
            return self._empty_allocation_plan(pending_rows)

        for line in request_lines:
            line_vars = [
                variable
                for (request_index, line_index, _equipment_id), variable in assignments.items()
                if request_index == line["requestIndex"] and line_index == line["lineIndex"]
            ]
            problem += pulp.lpSum(line_vars) <= 1

        for equipment_id, equipment in equipment_by_id.items():
            problem += (
                pulp.lpSum(
                    line["quantity"] * variable
                    for line in request_lines
                    for (request_index, line_index, assigned_equipment_id), variable in assignments.items()
                    if assigned_equipment_id == equipment_id
                    and request_index == line["requestIndex"]
                    and line_index == line["lineIndex"]
                )
                <= max(0, int(equipment["available_quantity"] or 0))
            )

        for request_index, line_count in request_line_count.items():
            line_vars = [
                variable
                for (assigned_request_index, _line_index, _equipment_id), variable in assignments.items()
                if assigned_request_index == request_index
            ]
            problem += fulfilled_vars[request_index] <= pulp.lpSum(line_vars) / max(1, line_count)
            problem += fulfilled_vars[request_index] >= pulp.lpSum(line_vars) - line_count + 1

        objective_terms = []
        for (request_index, line_index, equipment_id), variable in assignments.items():
            request = pending_rows[request_index]
            equipment = equipment_by_id[equipment_id]
            priority_weight = float(priority_weights.get(str(request["priority_level"] or "Normal").lower(), 1.0))
            equipment_weight = float(equipment_weights.get(str(equipment["equipment_name"] or "").lower(), 1.0))
            quantity = next(
                line["quantity"]
                for line in request_lines
                if line["requestIndex"] == request_index and line["lineIndex"] == line_index
            )
            objective_terms.append((priority_weight + equipment_weight) * quantity * variable)
        objective_terms.extend(100 * variable for variable in fulfilled_vars.values())
        problem += pulp.lpSum(objective_terms)

        problem.solve(pulp.PULP_CBC_CMD(msg=False))
        status = str(pulp.LpStatus.get(problem.status, "Unknown")).lower()
        if status not in {"optimal", "feasible"}:
            self._last_optimizer_status = f"bilp_{status}_fallback"
            return None

        self._last_optimizer_status = f"bilp_{status}"
        return self._allocation_plan_from_solution(pending_rows, request_lines, assignments)

    def _allocation_plan_from_solution(
        self,
        pending_rows: list[Any],
        request_lines: list[dict[str, Any]],
        assignments: dict[tuple[int, int, int], Any],
    ) -> list[dict[str, Any]]:
        allocation_plan = []
        lines_by_request: dict[int, list[dict[str, Any]]] = {}
        for line in request_lines:
            allocated_quantity = 0
            for (request_index, line_index, _equipment_id), variable in assignments.items():
                if request_index == line["requestIndex"] and line_index == line["lineIndex"] and float(variable.value() or 0) >= 0.5:
                    allocated_quantity = line["quantity"]
                    break
            lines_by_request.setdefault(line["requestIndex"], []).append(
                {
                    "equipmentName": line["equipmentName"],
                    "requestedQuantity": line["quantity"],
                    "allocatedQuantity": allocated_quantity,
                }
            )

        for request_index, request in enumerate(pending_rows):
            line_items = lines_by_request.get(request_index, [])
            allocated_total = sum(int(item["allocatedQuantity"]) for item in line_items)
            requested_total = int(request["requested_quantity"] or 0)
            allocation_plan.append(
                {
                    "reservationCode": request["reservation_code"],
                    "organizationName": request["organization_name"],
                    "eventDate": request["event_date_time"].date().isoformat(),
                    "priorityLevel": request["priority_level"],
                    "requestedQuantity": requested_total,
                    "allocatedQuantity": allocated_total,
                    "lineItems": line_items,
                    "status": "partial" if allocated_total < requested_total else "allocated",
                }
            )
        return allocation_plan

    def _empty_allocation_plan(self, pending_rows: list[Any]) -> list[dict[str, Any]]:
        allocation_plan = []
        for request in pending_rows:
            line_items = [
                {
                    "equipmentName": str(item.get("equipmentName") or item.get("name") or "Unknown"),
                    "requestedQuantity": max(0, int(item.get("quantity") or 0)),
                    "allocatedQuantity": 0,
                }
                for item in self._parse_requested_items(request["requested_equipment_list"])
            ]
            allocation_plan.append(
                {
                    "reservationCode": request["reservation_code"],
                    "organizationName": request["organization_name"],
                    "eventDate": request["event_date_time"].date().isoformat(),
                    "priorityLevel": request["priority_level"],
                    "requestedQuantity": int(request["requested_quantity"] or 0),
                    "allocatedQuantity": 0,
                    "lineItems": line_items,
                    "status": "partial",
                }
            )
        return allocation_plan

    def _artifact_model_name(self, artifact: dict[str, Any] | None, fallback: str) -> str:
        if artifact is None:
            return fallback
        metadata = artifact.get("metadata", {})
        return str(metadata.get("modelName") or fallback)

    def _artifact_utilization_model_name(self, artifact: dict[str, Any] | None, fallback: str) -> str:
        if artifact is None:
            return fallback
        metadata = artifact.get("metadata", {})
        return str(metadata.get("utilizationModelName") or fallback)

    def _build_notes(self, artifact: dict[str, Any] | None) -> str:
        solver_note = (
            "B-ILP solver is active."
            if self._last_optimizer_status in {"bilp_optimal", "bilp_feasible"}
            else "B-ILP solver fell back to sequential allocation."
        )
        artifact_note = (
            "Weekly trained .pkl allocation optimizer profile is active."
            if artifact is not None
            else "FastAPI storage path is live. Weekly training will replace this placeholder output."
        )
        return f"{artifact_note} {solver_note}"

    def _build_equipment_usage_map(self, connection: Connection, start_date: date, end_date: date) -> dict[str, int]:
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
            for item in self._parse_requested_items(row["requested_equipment_list"]):
                item_name = str(item.get("equipmentName") or item.get("name") or "").strip()
                if not item_name:
                    continue
                usage[item_name.lower()] = usage.get(item_name.lower(), 0) + max(1, int(item.get("quantity") or 1))

        return usage

    def _build_utilization_by_category(
        self,
        equipment_rows: list[Any],
        usage_map: dict[str, int],
        artifact: dict[str, Any] | None = None,
    ) -> list[dict[str, Any]]:
        category_usage: dict[str, int] = {}
        category_total: dict[str, int] = {}
        utilization_model = artifact.get("utilizationModel") if artifact else None

        for row in equipment_rows:
            category = str(row["equipment_category"] or "Others")
            equipment_name = str(row["equipment_name"] or "")
            total_quantity = max(1, int(row["total_quantity"] or 1))
            category_total[category] = category_total.get(category, 0) + total_quantity
            usage_count = usage_map.get(equipment_name.lower(), 0)
            if utilization_model is not None:
                try:
                    usage_count = max(0, int(round(float(utilization_model.predict([self._utilization_features(row, usage_map)])[0]))))
                except Exception:
                    usage_count = usage_map.get(equipment_name.lower(), 0)
            category_usage[category] = category_usage.get(category, 0) + usage_count

        result = [
            {
                "label": category,
                "value": round(min(100.0, (category_usage.get(category, 0) / max(1, total)) * 100), 1),
            }
            for category, total in category_total.items()
        ]
        return sorted(result, key=lambda item: (-float(item["value"]), str(item["label"])))[:5]

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

    def _resolve_equipment_trend_config(self, config: dict[str, Any]) -> dict[str, int]:
        trend_config = config.get("equipmentTrends", {}) if isinstance(config.get("equipmentTrends"), dict) else {}
        item_limit = 25
        page_size = max(1, min(item_limit, int(trend_config.get("pageSize") or 5)))
        return {
            "topEquipmentPage": max(1, int(trend_config.get("topEquipmentPage") or 1)),
            "preparationDecisionPage": max(1, int(trend_config.get("preparationDecisionPage") or 1)),
            "pageSize": page_size,
            "maxPages": max(1, item_limit // page_size),
        }

    def _paginate_items(self, items: list[dict[str, Any]], page: int, page_size: int, max_pages: int) -> dict[str, Any]:
        capped_total = min(len(items), max(1, page_size) * max(1, max_pages))
        capped_items = items[:capped_total]
        total_pages = max(1, (capped_total + page_size - 1) // page_size)
        normalized_page = max(1, min(page, total_pages))
        start_index = (normalized_page - 1) * page_size
        end_index = start_index + page_size
        return {
            "items": capped_items[start_index:end_index],
            "pagination": {
                "page": normalized_page,
                "pageSize": page_size,
                "totalItems": capped_total,
                "availableItems": len(items),
                "totalPages": total_pages,
                "maxPages": max_pages,
            },
        }

    def _build_top_equipment(
        self,
        equipment_rows: list[Any],
        usage_map: dict[str, int],
        today_usage_map: dict[str, int] | None = None,
    ) -> list[dict[str, Any]]:
        items = []
        today_usage = today_usage_map or {}
        for row in equipment_rows:
            equipment_name = str(row["equipment_name"] or "")
            equipment_key = equipment_name.lower()
            usage_count = usage_map.get(equipment_key, 0)
            if usage_count <= 0:
                continue
            total_quantity = max(1, int(row["total_quantity"] or 1))
            items.append(
                {
                    "name": equipment_name,
                    "count": usage_count,
                    "todayCount": today_usage.get(equipment_key, 0),
                    "rate": round(min(100.0, (usage_count / total_quantity) * 100), 1),
                }
            )
        return sorted(items, key=lambda item: (-int(item["count"]), str(item["name"])))

    def _build_possible_borrowed_equipment(
        self,
        equipment_rows: list[Any],
        current_usage_map: dict[str, int],
        previous_year_usage_map: dict[str, int],
    ) -> list[dict[str, Any]]:
        candidates = []

        for row in equipment_rows:
            equipment_name = str(row["equipment_name"] or "")
            equipment_key = equipment_name.lower()
            if not equipment_key:
                continue

            current_usage = current_usage_map.get(equipment_key, 0)
            previous_year_usage = previous_year_usage_map.get(equipment_key, 0)
            if current_usage <= 0:
                continue

            total_quantity = max(1, int(row["total_quantity"] or 1))
            predicted_demand = self._predict_equipment_demand(current_usage, previous_year_usage, total_quantity)
            prediction_gap = max(0.0, predicted_demand - float(current_usage))
            score = (predicted_demand * 1.6) + (current_usage * 0.4)
            candidates.append(
                {
                    "name": equipment_name,
                    "count": current_usage,
                    "currentUsage": current_usage,
                    "previousYearCount": previous_year_usage,
                    "predictedDemand": predicted_demand,
                    "predictionGap": round(prediction_gap, 2),
                    "totalQuantity": total_quantity,
                    "score": score,
                    "reason": self._build_candidate_reason(current_usage, previous_year_usage, predicted_demand),
                }
            )

        ordered_candidates = sorted(candidates, key=lambda item: (-float(item["score"]), str(item["name"])))
        return [
            {
                "name": item["name"],
                "count": item["count"],
                "currentUsage": item["currentUsage"],
                "previousYearCount": item["previousYearCount"],
                "predictedDemand": item["predictedDemand"],
                "predictionGap": item["predictionGap"],
                "totalQuantity": item["totalQuantity"],
                "score": round(float(item["score"]), 2),
                "reason": item["reason"],
                "decision": self._build_candidate_decision(item["count"], item["predictedDemand"]),
                "action": self._build_candidate_action(item["count"], item["predictedDemand"]),
            }
            for item in ordered_candidates
        ]

    def _predict_equipment_demand(self, current_usage: int, previous_year_usage: int, total_quantity: int) -> float:
        seasonal_component = previous_year_usage * 0.45
        current_component = current_usage * 0.65
        positive_trend_component = max(0, current_usage - previous_year_usage) * 0.25
        capacity_pressure_component = max(0, current_usage - total_quantity) * 0.15
        predicted = current_component + seasonal_component + positive_trend_component + capacity_pressure_component
        return round(max(float(current_usage), predicted), 2)

    def _build_candidate_reason(self, current_usage: int, previous_year_usage: int, predicted_demand: float) -> str:
        if predicted_demand > current_usage:
            return "Forecasted demand is higher than current usage."
        if previous_year_usage > current_usage:
            return "Past same-date demand is higher, but the forecast keeps this item near current usage."
        if current_usage > 0:
            return "Current demand is already active in this range."
        return "Historical same-date demand exists."

    def _build_candidate_decision(self, current_usage: int, predicted_demand: float) -> str:
        if predicted_demand > current_usage:
            return "Prepare for forecast"
        return "Keep prepared"

    def _build_candidate_action(self, current_usage: int, predicted_demand: float) -> str:
        if predicted_demand > current_usage:
            gap = max(1, round(predicted_demand - current_usage))
            return f"Reserve a buffer for about {gap} forecasted uses."
        return "Monitor availability and avoid lending all units early."

    def _count_completed_reservations(self, connection: Connection, start_date: date, end_date: date) -> int:
        row = connection.execute(
            """
            SELECT COUNT(*)::int AS completed_count
              FROM reservations
             WHERE event_date_time >= %s
               AND event_date_time <= %s
               AND LOWER(COALESCE(current_status, '')) IN ('completed', 'returned')
            """,
            (datetime.combine(start_date, time.min), datetime.combine(end_date, time.max)),
        ).fetchone()
        return int(row["completed_count"] if row is not None else 0)

    def _parse_requested_items(self, requested_items: Any) -> list[dict[str, Any]]:
        if not requested_items:
            return []
        if isinstance(requested_items, str):
            return json.loads(requested_items)
        return list(requested_items)
