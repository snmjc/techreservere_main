from datetime import UTC, datetime
from pathlib import Path
import re
import shutil
from typing import Any

import joblib

from app.settings import settings


FORECAST_ARTIFACT = "demand_forecast.pkl"
READINESS_ARTIFACT = "readiness_random_forest.pkl"
ALLOCATION_ARTIFACT = "allocation_optimizer.pkl"
ACTIVE_SET_FILE = "active_model_set.json"
DEFAULT_MODEL_SET = "default"
ARTIFACT_FILENAMES = [FORECAST_ARTIFACT, READINESS_ARTIFACT, ALLOCATION_ARTIFACT]


def normalize_model_set_name(value: str | None) -> str:
    normalized = re.sub(r"[^a-zA-Z0-9_-]+", "-", str(value or "").strip().lower())
    normalized = normalized.strip("-_")
    return normalized[:80] or DEFAULT_MODEL_SET


class ModelArtifactStore:
    def __init__(self, model_dir: str | None = None) -> None:
        self.model_dir = Path(model_dir or settings.analytics_model_dir)

    def active_set(self) -> str:
        state = self._load_active_state()
        return normalize_model_set_name(state.get("activeSet"))

    def active_artifacts(self) -> dict[str, str]:
        state = self._load_active_state()
        raw_artifacts = state.get("activeArtifacts", {})
        if not isinstance(raw_artifacts, dict):
            raw_artifacts = {}
        active_set = self.active_set()
        return {
            filename: normalize_model_set_name(raw_artifacts.get(filename) or active_set)
            for filename in ARTIFACT_FILENAMES
        }

    def activate_set(self, set_name: str) -> dict[str, Any]:
        normalized_set_name = normalize_model_set_name(set_name)
        if not self._set_exists(normalized_set_name):
            raise ValueError(f"Model set '{normalized_set_name}' does not exist.")

        self.model_dir.mkdir(parents=True, exist_ok=True)
        state = {
            "activeSet": normalized_set_name,
            "activeArtifacts": {
                filename: normalized_set_name
                for filename in ARTIFACT_FILENAMES
            },
            "activatedAt": datetime.now(UTC).isoformat(),
        }
        self._save_active_state(state)
        return state

    def activate_artifact(self, filename: str, set_name: str) -> dict[str, Any]:
        self._validate_artifact_filename(filename)
        normalized_set_name = normalize_model_set_name(set_name)
        artifact_path = self._artifact_path(filename, normalized_set_name)
        legacy_path = self._legacy_artifact_path(filename, normalized_set_name)
        if not artifact_path.exists() and (legacy_path is None or not legacy_path.exists()):
            raise ValueError(f"Artifact '{filename}' was not found in model set '{normalized_set_name}'.")

        state = self._load_active_state()
        active_artifacts = self.active_artifacts()
        active_artifacts[filename] = normalized_set_name
        state["activeSet"] = normalize_model_set_name(state.get("activeSet"))
        state["activeArtifacts"] = active_artifacts
        state["activatedAt"] = datetime.now(UTC).isoformat()
        self._save_active_state(state)
        return state

    def load(self, filename: str, set_name: str | None = None) -> dict[str, Any] | None:
        self._validate_artifact_filename(filename)
        path = self._artifact_path(filename, set_name)
        if not path.exists():
            path = self._legacy_artifact_path(filename, set_name)
        if path is None or not path.exists():
            return None
        try:
            artifact = joblib.load(path)
        except Exception:
            return None
        return artifact if isinstance(artifact, dict) else None

    def save(self, filename: str, artifact: dict[str, Any], set_name: str | None = None) -> Path:
        self._validate_artifact_filename(filename)
        normalized_set_name = normalize_model_set_name(set_name)
        path = self._artifact_path(filename, normalized_set_name)
        path.parent.mkdir(parents=True, exist_ok=True)
        temporary_path = path.with_suffix(f"{path.suffix}.tmp")
        artifact.setdefault("metadata", {})
        artifact["metadata"].setdefault("trainedAt", datetime.now(UTC).isoformat())
        artifact["metadata"]["modelSet"] = normalized_set_name
        joblib.dump(artifact, temporary_path)
        temporary_path.replace(path)
        return path

    def describe(self, filename: str, set_name: str | None = None) -> dict[str, Any]:
        self._validate_artifact_filename(filename)
        normalized_set_name = normalize_model_set_name(set_name or self.active_set())
        path = self._artifact_path(filename, normalized_set_name)
        legacy_path = self._legacy_artifact_path(filename, normalized_set_name)
        actual_path = path if path.exists() or legacy_path is None else legacy_path
        artifact = self.load(filename, normalized_set_name)
        metadata = artifact.get("metadata", {}) if artifact else {}
        return {
            "setName": normalized_set_name,
            "artifact": filename,
            "path": str(actual_path),
            "exists": actual_path.exists(),
            "active": self.active_artifacts().get(filename) == normalized_set_name,
            "trainedAt": metadata.get("trainedAt"),
            "trainingRows": metadata.get("trainingRows"),
            "score": metadata.get("score"),
        }

    def list_sets(self) -> dict[str, Any]:
        active_set = self.active_set()
        active_artifacts = self.active_artifacts()
        set_names = set()
        self.model_dir.mkdir(parents=True, exist_ok=True)
        for child in self.model_dir.iterdir():
            if child.is_dir():
                set_names.add(child.name)
        if any((self.model_dir / filename).exists() for filename in ARTIFACT_FILENAMES):
            set_names.add(DEFAULT_MODEL_SET)
        if not set_names:
            set_names.add(active_set)

        sets = []
        for set_name in sorted(set_names):
            artifacts = [self.describe(filename, set_name) for filename in ARTIFACT_FILENAMES]
            trained_at_values = [artifact["trainedAt"] for artifact in artifacts if artifact.get("trainedAt")]
            sets.append(
                {
                    "setName": set_name,
                    "active": set_name == active_set,
                    "complete": all(artifact["exists"] for artifact in artifacts),
                    "trainedAt": max(trained_at_values) if trained_at_values else None,
                    "artifacts": artifacts,
                }
            )

        return {
            "activeSet": active_set,
            "activeArtifacts": active_artifacts,
            "modelDir": str(self.model_dir),
            "sets": sets,
        }

    def rename_set(self, current_name: str, new_name: str) -> dict[str, Any]:
        current_set_name = normalize_model_set_name(current_name)
        new_set_name = normalize_model_set_name(new_name)
        if current_set_name == DEFAULT_MODEL_SET:
            raise ValueError("The default model set cannot be renamed.")
        if current_set_name == new_set_name:
            return {"setName": current_set_name, "renamedTo": new_set_name, "activeSet": self.active_set()}
        if not self._set_exists(current_set_name):
            raise ValueError(f"Model set '{current_set_name}' does not exist.")
        if self._set_exists(new_set_name):
            raise ValueError(f"Model set '{new_set_name}' already exists.")

        current_path = self.model_dir / current_set_name
        new_path = self.model_dir / new_set_name
        current_path.rename(new_path)
        state = self._load_active_state()
        if normalize_model_set_name(state.get("activeSet")) == current_set_name:
            state["activeSet"] = new_set_name
        active_artifacts = self.active_artifacts()
        state["activeArtifacts"] = {
            filename: new_set_name if set_name == current_set_name else set_name
            for filename, set_name in active_artifacts.items()
        }
        self._save_active_state(state)

        return {
            "setName": current_set_name,
            "renamedTo": new_set_name,
            "activeSet": self.active_set(),
            "activeArtifacts": self.active_artifacts(),
        }

    def delete_set(self, set_name: str) -> dict[str, Any]:
        normalized_set_name = normalize_model_set_name(set_name)
        if normalized_set_name == DEFAULT_MODEL_SET:
            for filename in ARTIFACT_FILENAMES:
                legacy_path = self.model_dir / filename
                if legacy_path.exists():
                    legacy_path.unlink()

        set_path = self.model_dir / normalized_set_name
        deleted = False
        if set_path.exists() and set_path.is_dir():
            shutil.rmtree(set_path)
            deleted = True

        active_set = self.active_set()
        if active_set == normalized_set_name:
            fallback_set = self._first_existing_set() or DEFAULT_MODEL_SET
            if self._set_exists(fallback_set):
                self.activate_set(fallback_set)
            else:
                active_state_path = self.model_dir / ACTIVE_SET_FILE
                if active_state_path.exists():
                    active_state_path.unlink()
        else:
            state = self._load_active_state()
            active_artifacts = self.active_artifacts()
            changed = False
            for filename, active_artifact_set in list(active_artifacts.items()):
                if active_artifact_set == normalized_set_name:
                    active_artifacts[filename] = self.active_set()
                    changed = True
            if changed:
                state["activeArtifacts"] = active_artifacts
                self._save_active_state(state)

        return {
            "setName": normalized_set_name,
            "deleted": deleted,
            "activeSet": self.active_set(),
            "activeArtifacts": self.active_artifacts(),
        }

    def _artifact_path(self, filename: str, set_name: str | None = None) -> Path:
        self._validate_artifact_filename(filename)
        active_set = self.active_artifacts().get(filename, self.active_set())
        return self.model_dir / normalize_model_set_name(set_name or active_set) / filename

    def _legacy_artifact_path(self, filename: str, set_name: str | None = None) -> Path | None:
        self._validate_artifact_filename(filename)
        active_set = self.active_artifacts().get(filename, self.active_set())
        if normalize_model_set_name(set_name or active_set) != DEFAULT_MODEL_SET:
            return None
        return self.model_dir / filename

    def _set_exists(self, set_name: str) -> bool:
        normalized_set_name = normalize_model_set_name(set_name)
        set_path = self.model_dir / normalized_set_name
        if set_path.exists() and any((set_path / filename).exists() for filename in ARTIFACT_FILENAMES):
            return True
        return normalized_set_name == DEFAULT_MODEL_SET and any((self.model_dir / filename).exists() for filename in ARTIFACT_FILENAMES)

    def _first_existing_set(self) -> str | None:
        sets = [item["setName"] for item in self.list_sets()["sets"] if item["complete"]]
        return sets[0] if sets else None

    def _load_active_state(self) -> dict[str, Any]:
        path = self.model_dir / ACTIVE_SET_FILE
        if not path.exists():
            return {"activeSet": DEFAULT_MODEL_SET}
        try:
            state = joblib.load(path)
        except Exception:
            return {"activeSet": DEFAULT_MODEL_SET}
        return state if isinstance(state, dict) else {"activeSet": DEFAULT_MODEL_SET}

    def _save_active_state(self, state: dict[str, Any]) -> None:
        self.model_dir.mkdir(parents=True, exist_ok=True)
        temporary_path = (self.model_dir / ACTIVE_SET_FILE).with_suffix(".json.tmp")
        joblib.dump(state, temporary_path)
        temporary_path.replace(self.model_dir / ACTIVE_SET_FILE)

    def _validate_artifact_filename(self, filename: str) -> None:
        if filename not in ARTIFACT_FILENAMES:
            raise ValueError(f"Unsupported model artifact '{filename}'.")
