# Reports & Analytics Validation Guide

This guide explains what the current Reports & Analytics page shows, where the data comes from, and how to verify it yourself.

## What the page is doing

The admin analytics page in Vue reads from the Symfony API first. The API returns stored analytics snapshots from PostgreSQL, and if no stored snapshot exists it falls back to live aggregation.

Current flow:

1. Vue loads `ReportsAnalytics.vue`
2. Vue calls Symfony analytics endpoints
3. Symfony reads the latest stored analytics from PostgreSQL
4. FastAPI writes analytics runs and result payloads into PostgreSQL
5. Vue renders the stored results

## Main data sources

- `equipment` — drives readiness and utilization views
- `reservations` — drives demand forecasting and allocation signals
- `analytics_configurations` — stores analytics settings
- `analytics_runs` — stores each scheduled or manual run
- `analytics_results` — stores the actual outputs shown in the UI

## What each analytics section means

### Forecast

This is the seasonal demand view.

- Source: reservation submission dates
- Purpose: estimate future equipment demand
- Current implementation: placeholder-ready output, but it already stores real historical counts

What to check:

- Are there reservation dates in the selected time range?
- Does the chart show multiple points instead of a blank graph?
- Does the generated timestamp update after a new run?

### Readiness

This is the equipment readiness view.

- Source: `equipment.total_quantity` and `equipment.available_quantity`
- Purpose: classify items as ready or needing attention
- Logic: higher availability ratio means healthier readiness

What to check:

- Equipment with low availability should appear as `watch`
- Equipment with healthy availability should appear as `ready`
- Maintenance or unavailable items should stand out

### Allocation

This is the scheduling and reservation optimization view.

- Source: reservation queue and status
- Purpose: show pending allocation pressure
- Current implementation: stores the number of pending requests and a placeholder plan

What to check:

- Pending requests should be greater than zero if you seeded open reservations
- The optimization panel should not be blank once analytics results are stored

## How to validate manually

### 1. Confirm seed data exists

Run:

```sql
SELECT COUNT(*) FROM equipment;
SELECT COUNT(*) FROM reservations;
SELECT COUNT(*) FROM analytics_runs;
SELECT COUNT(*) FROM analytics_results;
```

### 2. Trigger one analytics run

The FastAPI service exposes:

```text
POST http://analytics-service:9000/analytics/run-daily-check
GET  http://analytics-service:9000/health
```

In Docker, the service is private. The easiest way to trigger it is from inside the container or through the existing startup scheduler.

### 3. Check the stored results

Use SQL:

```sql
SELECT run_identifier, run_type, status, started_at, completed_at
FROM analytics_runs
ORDER BY started_at DESC;

SELECT result_type, model_name, generated_at
FROM analytics_results
ORDER BY generated_at DESC;
```

### 4. Open the UI

- Dashboard: `http://localhost:5173/admin/dashboard`
- Analytics page: `http://localhost:5173/admin/reports-analytics`

### 5. Compare UI with DB

If the page is healthy, the visible values should line up with the stored result payloads in `analytics_results`.

## Example expectations with the current demo seed

After the richer demo seed, you should see:

- several forecast points across different dates
- a mix of `ready` and `watch` readiness labels
- a non-zero pending request count
- one or more completed analytics runs in the DB

## Troubleshooting

### Blank page

Usually means one of these:

- no stored analytics results yet
- frontend could not reach the API
- backend returned an auth error
- the analytics run never completed

### 500 error

Usually means:

- database schema drift
- a missing migration
- a bad environment variable
- a failed analytics run

### No stored results

Run the analytics job again and confirm:

- `analytics_runs.status = completed`
- `analytics_results` has 3 rows per run

## Recommended next step

For real validation, seed:

- 5 to 10 equipment rows
- 30 to 60 reservations spread across at least two years
- at least one pending, approved, completed, and rejected reservation

That gives the forecast, readiness, and allocation panels enough variation to look meaningful.

## Demo seed for seasonal forecasting

If you specifically want to test the May 25 to Jun 23 seasonal window across two years, use:

- `docs/demo-seeds/two-year-may25-jun23-forecast.sql`

What it gives you:

- 9 equipment rows with available, maintenance, and unavailable states
- 50 reservations across roughly two years
- heavier demand in late May and June
- lighter demand in the rest of the year
- 3 pending requests, plus approved, completed, and rejected examples
- demo borrower accounts and matching equipment names in every request payload

How to run it:

```bash
Get-Content -Raw docs/demo-seeds/two-year-may25-jun23-forecast.sql | docker compose -f compose.dev.yml exec -T database psql -U techreserve_user -d techreserve
```

How to remove it:

```bash
Get-Content -Raw docs/demo-seeds/two-year-may25-jun23-forecast-cleanup.sql | docker compose -f compose.dev.yml exec -T database psql -U techreserve_user -d techreserve
```

The cleanup removes demo reservations and borrowers, removes demo-only equipment, and restores any equipment rows that the seed adjusted.
