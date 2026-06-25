import json
from typing import Any


def json_dumps(value: dict[str, Any] | list[Any]) -> str:
    return json.dumps(value, default=str)


def average(values: list[Any], fallback: float = 0.0) -> float:
    numeric_values = [float(value) for value in values if value is not None]
    if not numeric_values:
        return fallback
    return sum(numeric_values) / len(numeric_values)
