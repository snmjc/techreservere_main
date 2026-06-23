# Analytics Modeling Plan

## TLDR

TechReserve should keep the existing Vue frontend and Symfony backend as the main application, then add a small Python FastAPI analytics service for the modeling layer.

Vue and Symfony are already enough for dashboards, authentication, database-backed APIs, and deployment orchestration. The requested analytics methods are better implemented in Python because the ecosystem is stronger for SARIMA, Random Forest, and Binary Linear Programming.

## Target Methods

### SARIMA

Purpose: seasonal time series prediction of equipment demand using historical reservation data.

Recommended implementation:

- Use Python `statsmodels`.
- Train on reservation submission dates and/or event dates.
- Forecast demand by equipment category, equipment item, or total daily demand.
- Return actual series, forecast series, confidence bands, peak dates, and model metadata.

### Random Forest

Purpose: decision tree classification of equipment readiness from usage and maintenance features.

Recommended implementation:

- Use Python `scikit-learn`.
- Predict readiness or risk class such as `ready`, `watch`, `at_risk`, or `unavailable`.
- Use features such as usage frequency, available quantity ratio, overdue linkage, maintenance state, operational status, recent reservation volume, and return history.
- Return readiness label, probability/confidence, top contributing features, and recommended action.

### Binary Linear Programming

Purpose: constraint-based optimization of equipment allocation and reservation scheduling.

Recommended implementation:

- Use Python `OR-Tools` or `PuLP`.
- Model allocation choices as binary decision variables.
- Respect constraints such as equipment availability, quantity limits, reservation time windows, venue requirements, role priority, conflict avoidance, and maintenance blocks.
- Return allocation plan, unassigned requests, violated constraints if infeasible, and objective score.

## Proposed Architecture

```text
Vue dashboard
  -> Symfony API
    -> Python FastAPI analytics service
      -> SARIMA / Random Forest / Binary LP models
    -> Symfony normalizes and returns analytics response
  -> Vue renders charts, risk bands, and optimization results
```

## Responsibilities

### Vue

- Display analytics dashboards and reports.
- Keep existing pages such as Reports & Analytics and Admin Dashboard.
- Render forecast charts, readiness classifications, optimization results, and PDF exports.

### Symfony

- Remain the main API and auth boundary.
- Fetch source data from PostgreSQL through existing repositories.
- Enforce Clerk/auth role checks before analytics access.
- Call the Python analytics service internally.
- Normalize Python responses into the current frontend response shape where possible.

### Python FastAPI

- Own the actual model execution.
- Expose internal endpoints for forecasting, readiness classification, and optimization.
- Keep model code isolated from the main Symfony application.
- Return structured JSON responses for Symfony to consume.

## Suggested FastAPI Endpoints

```text
POST /analytics/forecast/sarima
POST /analytics/readiness/random-forest
POST /analytics/allocation/optimize
GET  /health
```

## Deployment Plan

1. Add an `analytics-service/` directory with FastAPI.
2. Add the service to `compose.yml` and `compose.prod.yml`.
3. Keep the analytics service private inside Docker networking.
4. Add an internal Symfony environment variable such as `ANALYTICS_SERVICE_URL=http://analytics-service:9000`.
5. Add a Symfony client/service that calls FastAPI.
6. Replace the current heuristic analytics outputs incrementally with real model outputs.

## Fastest MVP Scope

1. Keep the existing Vue analytics UI unchanged.
2. Add FastAPI with mock-compatible JSON responses first.
3. Wire Symfony to call FastAPI.
4. Implement SARIMA first because the current UI already has demand forecasting.
5. Implement Random Forest readiness next.
6. Implement Binary Linear Programming last because it has the most constraints and needs clearer scheduling rules.

## Recommendation

For quick deployment and credible analytics, use:

- Vue for presentation.
- Symfony for app API, auth, database access, and deployment integration.
- Python FastAPI for SARIMA, Random Forest, and Binary Linear Programming.

This avoids a risky rewrite while giving the analytics layer the right tooling.
