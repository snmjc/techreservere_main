from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    database_url: str
    analytics_daily_cron_hour: int = 2
    analytics_daily_cron_minute: int = 0
    analytics_scheduler_enabled: bool = True

    model_config = SettingsConfigDict(env_file=None, extra="ignore")


settings = Settings()
