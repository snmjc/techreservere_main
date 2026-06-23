from contextlib import asynccontextmanager

from apscheduler.schedulers.background import BackgroundScheduler
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

from app.analytics_runner import AnalyticsRunner
from app.database import get_connection
from app.settings import settings

runner = AnalyticsRunner()
scheduler = BackgroundScheduler(timezone="UTC")


class RunRequest(BaseModel):
    scenario: str | None = None


def scheduled_daily_check() -> None:
    with get_connection() as connection:
        runner.run_daily_check(connection, triggered_by="scheduler")


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
        with get_connection() as connection:
            triggered_by = "manual"
            if request and request.scenario:
                triggered_by = f"manual:{request.scenario}"
            runner.prepare_scenario(connection, request.scenario if request else None)
            return runner.run_daily_check(connection, triggered_by=triggered_by)
    except Exception as error:
        raise HTTPException(status_code=500, detail=str(error)) from error
