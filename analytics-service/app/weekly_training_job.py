"""Safe entrypoint for TechReserve weekly model training."""

import json

from app.database import get_connection
from app.model_trainer import AnalyticsModelTrainer
from app.model_artifacts import ARTIFACT_FILENAMES


def main() -> None:
    trainer = AnalyticsModelTrainer()

    with get_connection() as connection:
        result = trainer.train_all(
            connection,
            triggered_by="weekly_scheduler",
            activate=False,
        )

    artifacts = result["artifacts"]
    model_set = result["setName"]

    required_models = ("forecast", "readiness", "allocation")

    all_trained = all(
        isinstance(artifacts.get(name), dict)
        and artifacts[name].get("status") == "trained"
        for name in required_models
    )

    all_exist = all(
        trainer.artifact_store.describe(
            filename,
            model_set,
        )["exists"]
        for filename in ARTIFACT_FILENAMES
    )

    if all_trained and all_exist:
        trainer.artifact_store.activate_set(model_set)
        result["status"] = "activated"
        result["activeSet"] = model_set
    else:
        result["status"] = "skipped_activation"
        result["activeSet"] = (
            trainer.artifact_store.active_set()
        )

    print(json.dumps(result, indent=2, default=str))


if __name__ == "__main__":
    main()
