"""Tests for safe weekly model activation."""

import importlib
import json
import sys
import unittest
from pathlib import Path
from unittest.mock import MagicMock, patch


sys.path.insert(
    0,
    str(Path(__file__).resolve().parents[1]),
)

from app.model_artifacts import ARTIFACT_FILENAMES

weekly_job = importlib.import_module(
    "app.weekly_training_job"
)


class WeeklyTrainingJobTests(unittest.TestCase):

    def run_scenario(self, statuses, exists=True):
        trainer = MagicMock()
        trainer.artifact_store.active_set.return_value = "default"
        trainer.artifact_store.describe.return_value = {
            "exists": exists
        }

        trainer.train_all.return_value = {
            "status": "completed",
            "setName": "weekly-test",
            "activeSet": "default",
            "artifacts": {
                name: {"status": status}
                for name, status in zip(
                    ("forecast", "readiness", "allocation"),
                    statuses,
                )
            },
        }

        connection = MagicMock()
        manager = MagicMock()
        manager.__enter__.return_value = connection

        with patch.object(
            weekly_job,
            "AnalyticsModelTrainer",
            return_value=trainer,
        ), patch.object(
            weekly_job,
            "get_connection",
            return_value=manager,
        ), patch("builtins.print") as output:
            weekly_job.main()

        result = json.loads(output.call_args.args[0])
        return trainer, result

    def test_all_models_activate(self):
        trainer, result = self.run_scenario(
            ["trained", "trained", "trained"]
        )

        trainer.artifact_store.activate_set.assert_called_once_with(
            "weekly-test"
        )
        self.assertEqual(result["status"], "activated")

    def test_partial_training_preserves_active_set(self):
        trainer, result = self.run_scenario(
            ["trained", "skipped", "trained"]
        )

        trainer.artifact_store.activate_set.assert_not_called()
        self.assertEqual(result["status"], "skipped_activation")
        self.assertEqual(result["activeSet"], "default")

    def test_all_skipped_preserves_active_set(self):
        trainer, result = self.run_scenario(
            ["skipped", "skipped", "skipped"]
        )

        trainer.artifact_store.activate_set.assert_not_called()
        self.assertEqual(result["status"], "skipped_activation")

    def test_missing_artifact_prevents_activation(self):
        trainer, result = self.run_scenario(
            ["trained", "trained", "trained"],
            exists=False,
        )

        trainer.artifact_store.activate_set.assert_not_called()
        self.assertEqual(result["status"], "skipped_activation")


if __name__ == "__main__":
    unittest.main()
