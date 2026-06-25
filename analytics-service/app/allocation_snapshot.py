from datetime import date, datetime, time, timedelta
import json
from typing import Any

from psycopg import Connection


class AllocationSnapshotBuilder:
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
        allocation_plan = self._build_allocation_plan(pending_rows, equipment_rows)

        current_usage_map = self._build_equipment_usage_map(connection, start_date, end_date)
        previous_year_usage_map = self._build_equipment_usage_map(connection, previous_year_start, previous_year_end)
        utilization_by_category = self._build_utilization_by_category(equipment_rows, current_usage_map)
        utilization_comparison_by_category = self._build_utilization_by_category(equipment_rows, previous_year_usage_map)
        top_equipment = self._build_top_equipment(equipment_rows, current_usage_map, limit=10)
        possible_borrowed_equipment = self._build_possible_borrowed_equipment(
            equipment_rows,
            current_usage_map,
            previous_year_usage_map,
        )

        return {
            "modelName": "binary_linear_programming",
            "status": "placeholder_ready",
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
            "topEquipment": top_equipment,
            "possibleBorrowedEquipment": possible_borrowed_equipment,
            "summary": {
                "totalEquipment": len(equipment_rows),
                "activeReservations": len(allocation_plan),
                "pendingRequests": len(pending_rows),
                "completedThisPeriod": self._count_completed_reservations(connection, start_date, end_date),
            },
            "fulfilledCount": sum(1 for item in allocation_plan if item["status"] == "allocated"),
            "partialCount": sum(1 for item in allocation_plan if item["status"] == "partial"),
            "notes": "FastAPI storage path is live. Binary LP solving will replace this placeholder output.",
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
             ORDER BY submission_timestamp ASC, event_date_time ASC
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

    def _build_allocation_plan(self, pending_rows: list[Any], equipment_rows: list[Any]) -> list[dict[str, Any]]:
        allocation_plan: list[dict[str, Any]] = []
        remaining_capacity = {
            int(row["equipment_identifier"]): max(0, int(row["available_quantity"] or 0))
            for row in equipment_rows
        }

        for request in pending_rows:
            line_items: list[dict[str, Any]] = []
            allocated_total = 0
            requested_items = self._parse_requested_items(request["requested_equipment_list"])
            for item in requested_items:
                item_name = str(item.get("equipmentName") or item.get("name") or "Unknown")
                item_quantity = max(0, int(item.get("quantity") or 0))
                allocated_quantity = 0

                for equipment in equipment_rows:
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

    def _build_top_equipment(self, equipment_rows: list[Any], usage_map: dict[str, int], limit: int) -> list[dict[str, Any]]:
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
        return sorted(items, key=lambda item: (-int(item["count"]), str(item["name"])))[:limit]

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

            score = (previous_year_usage * 1.4) + (current_usage * 0.8)
            candidates.append(
                {
                    "name": equipment_name,
                    "count": current_usage,
                    "score": score,
                    "reason": self._build_candidate_reason(current_usage, previous_year_usage),
                }
            )

        ordered_candidates = sorted(candidates, key=lambda item: (-float(item["score"]), str(item["name"])))
        return [
            {"name": item["name"], "count": item["count"], "reason": item["reason"]}
            for item in ordered_candidates[:5]
        ]

    def _build_candidate_reason(self, current_usage: int, previous_year_usage: int) -> str:
        if previous_year_usage > current_usage:
            return f"Used {previous_year_usage} times on the same dates last year, so prepare it for seasonal demand."
        if current_usage > 0:
            return f"Already used {current_usage} times in this range, so keep it prepared."
        return f"Used {previous_year_usage} times historically on the same dates."

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
