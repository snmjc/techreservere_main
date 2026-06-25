FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

WORKDIR /app

COPY analytics-service/requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY analytics-service/app ./app
COPY docs/demo-seeds ./demo-seeds

EXPOSE 9000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "9000"]
