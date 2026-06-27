from contextlib import asynccontextmanager

from apscheduler.schedulers.background import BackgroundScheduler
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

from app.analytics_runner import AnalyticsRunner
from app.database import get_connection
from app.model_artifacts import ModelArtifactStore
from app.model_trainer import AnalyticsModelTrainer
from app.settings import settings

runner = AnalyticsRunner()
artifact_store = ModelArtifactStore()
model_trainer = AnalyticsModelTrainer()
scheduler = BackgroundScheduler(timezone="UTC")


class RunRequest(BaseModel):
    scenario: str | None = None
    historyDays: int | None = None
    startDate: str | None = None
    endDate: str | None = None


class TrainModelsRequest(BaseModel):
    setName: str | None = None
    activate: bool = True


class ActivateModelSetRequest(BaseModel):
    setName: str


class ActivateModelArtifactRequest(BaseModel):
    setName: str
    artifact: str


class RenameModelSetRequest(BaseModel):
    newName: str


def scheduled_daily_check() -> None:
    with get_connection() as connection:
        runner.run_daily_check(connection, triggered_by="scheduler")


def scheduled_weekly_training() -> None:
    with get_connection() as connection:
        model_trainer.train_all(connection, triggered_by="weekly_scheduler")


@asynccontextmanager
async def lifespan(app: FastAPI):
    if settings.analytics_scheduler_enabled:
        scheduler.add_job(
            scheduled_daily_check,
            "cron",
            hour=settings.analytics_daily_cron_hour,
            minute=settings.analytics_daily_cron_minute,
            id="daily_analytics_check",
            replace_existing=True,
        )
        scheduler.add_job(
            scheduled_weekly_training,
            "cron",
            day_of_week=settings.analytics_weekly_training_day_of_week,
            hour=settings.analytics_weekly_training_hour,
            minute=settings.analytics_weekly_training_minute,
            id="weekly_model_training",
            replace_existing=True,
        )
        scheduler.start()

    yield

    if scheduler.running:
        scheduler.shutdown(wait=False)


app = FastAPI(title="TechReserve Analytics Service", version="0.1.0", lifespan=lifespan)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/analytics/run-daily-check")
def run_daily_check(request: RunRequest | None = None) -> dict:
    try:
        runner.clear_cache()
        with get_connection() as connection:
            triggered_by = "manual"
            if request and request.scenario:
                triggered_by = f"manual:{request.scenario}"
            runner.prepare_scenario(connection, request.scenario if request else None)
            return runner.run_daily_check(
                connection,
                triggered_by=triggered_by,
                history_days=request.historyDays if request else None,
                start_date=request.startDate if request else None,
                end_date=request.endDate if request else None,
            )
    except Exception as error:
        raise HTTPException(status_code=500, detail=str(error)) from error


@app.post("/analytics/train-models")
def train_models(request: TrainModelsRequest | None = None) -> dict:
    try:
        runner.clear_cache()
        with get_connection() as connection:
            return model_trainer.train_all(
                connection,
                triggered_by="manual",
                set_name=request.setName if request else None,
                activate=request.activate if request else True,
            )
    except Exception as error:
        raise HTTPException(status_code=500, detail=str(error)) from error


@app.get("/analytics/model-artifacts")
def list_model_artifacts() -> dict:
    try:
        return artifact_store.list_sets()
    except Exception as error:
        raise HTTPException(status_code=500, detail=str(error)) from error


@app.post("/analytics/model-artifacts/activate")
def activate_model_set(request: ActivateModelSetRequest) -> dict:
    try:
        runner.clear_cache()
        return artifact_store.activate_set(request.setName)
    except ValueError as error:
        raise HTTPException(status_code=404, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(status_code=500, detail=str(error)) from error


@app.post("/analytics/model-artifacts/activate-artifact")
def activate_model_artifact(request: ActivateModelArtifactRequest) -> dict:
    try:
        runner.clear_cache()
        return artifact_store.activate_artifact(request.artifact, request.setName)
    except ValueError as error:
        raise HTTPException(status_code=404, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(status_code=500, detail=str(error)) from error


@app.patch("/analytics/model-artifacts/{set_name}")
def rename_model_set(set_name: str, request: RenameModelSetRequest) -> dict:
    try:
        runner.clear_cache()
        return artifact_store.rename_set(set_name, request.newName)
    except ValueError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(status_code=500, detail=str(error)) from error


@app.delete("/analytics/model-artifacts/{set_name}")
def delete_model_set(set_name: str) -> dict:
    try:
        runner.clear_cache()
        return artifact_store.delete_set(set_name)
    except Exception as error:
        raise HTTPException(status_code=500, detail=str(error)) from error


@app.post("/analytics/analyze-range")
def analyze_range(request: RunRequest) -> dict:
    if not request.startDate or not request.endDate:
        raise HTTPException(status_code=422, detail="startDate and endDate are required.")

    try:
        with get_connection() as connection:
            return runner.analyze_range(
                connection,
                history_days=request.historyDays or 30,
                start_date=request.startDate,
                end_date=request.endDate,
            )
    except Exception as error:
        raise HTTPException(status_code=500, detail=str(error)) from error


@app.post("/analytics/analyze-range/{section}")
def analyze_range_section(section: str, request: RunRequest) -> dict:
    if not request.startDate or not request.endDate:
        raise HTTPException(status_code=422, detail="startDate and endDate are required.")

    try:
        with get_connection() as connection:
            return runner.analyze_range_section(
                connection,
                section=section,
                history_days=request.historyDays or 30,
                start_date=request.startDate,
                end_date=request.endDate,
            )
    except ValueError as error:
        raise HTTPException(status_code=404, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(status_code=500, detail=str(error)) from error
