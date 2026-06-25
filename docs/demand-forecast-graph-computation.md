# Reports & Analytics Page Computation and Rule-Based Logic

This document explains the complete `/admin/reports-analytics` page:

- where the displayed data comes from;
- how the 7-, 14-, and 30-day filters work;
- how every graph, table, score, and summary value is calculated;
- how the rule-based forecasting, readiness, and allocation logic works;
- why the charts and narrative messages look the way they do;
- how the tooltips are calculated;
- how scenario and clean-data triggers differ from normal filtering.

## Important Model Status

The page currently uses **operational rule-based analytics** produced by the FastAPI analytics service.

The response names are compatible with the planned models:

- `sarima` for forecasting;
- `random_forest` for readiness;
- `binary_linear_programming` for allocation.

However, the current implementation is still marked `placeholder_ready`. It does not currently train:

- a statistical SARIMA model;
- a machine-learning Random Forest;
- a mathematical Binary Linear Programming solver.

Instead, it uses deterministic formulas, thresholds, weighted scores, and greedy allocation rules. The page should therefore be described as rule-based operational analytics until those models replace the current calculations.

## Page Data Flow

Normal page filtering uses this path:

```text
ReportsAnalytics.vue
    -> Symfony GET /api/v1/analytics/range-results
        -> FastAPI POST /analytics/analyze-range
            -> PostgreSQL reservations and equipment data
            -> forecast, readiness, and allocation calculations
        -> Symfony returns the FastAPI payload
    -> Vue normalizes and displays the result
```

The filter endpoint is read-only:

- it does not seed scenario data;
- it does not delete reservations;
- it does not create a stored `analytics_runs` record;
- it only calculates results for the requested date range.

## Date Filters

The available filters are:

| Filter | Start-date rule | Actual points | Forecast points | History points |
| --- | --- | ---: | ---: | ---: |
| Last 7 days | Today minus 6 days | 7 | 10 | 10 |
| Last 14 days | Today minus 13 days | 14 | 17 | 17 |
| Last 30 days | Today minus 29 days | 30 | 33 | 33 |

The selected range includes both the start date and end date.

```text
Selected-day count = end date - start date + 1
```

The forecast horizon is always three days when the filter is used:

```text
Displayed graph dates = selected-day count + 3 future dates
```

Changing the filter requests a new FastAPI calculation for the exact selected dates.

## Page Header and Controls

### Source Label

The source label reports whether the page is:

- loading FastAPI analytics;
- using FastAPI analytics for the selected range;
- unable to refresh and keeping the last completed result.

The existing visible result is retained if a later refresh fails.

### Refresh Button

Refresh repeats the read-only range calculation. It does not run a scenario.

### Model Cards

The three cards are descriptive labels:

1. Demand Trend Projection
2. Readiness Risk Bands
3. Allocation Efficiency

They do not contain separate calculations. They summarize the three FastAPI result groups used farther down the page.

---

# Demand Forecasting

## Source Data

Actual demand is calculated from `reservations.submission_timestamp`.

```text
Actual daily demand =
    number of reservations submitted on that calendar date
```

The forecast counts reservation records, not requested equipment quantity.

Every selected calendar date is included. A date without a reservation receives zero:

```text
No reservations on date -> Actual Demand = 0
```

This prevents missing dates from shortening or shifting the graph.

## Actual Demand Line

Actual Demand covers only the selected range:

- 7 points for Last 7 Days;
- 14 points for Last 14 Days;
- 30 points for Last 30 Days.

The line stops on the filter end date because future actual values do not exist.

## Forecasted Demand Line

Forecasted Demand covers:

```text
selected dates + 3 future dates
```

Forecast values are calculated on the selected dates as well as the future dates. The overlap allows the UI to compare model expectation against the observed result.

For each displayed date:

```text
Forecast =
    (
        Weekday Average × 0.35
        + Month Average × 0.45
        + Recent Average × 0.20
    )
    × Seasonal Multiplier
    × (1 + Trend Adjustment)
```

The final value is:

- limited to a minimum of zero;
- rounded to two decimal places.

### Full Baseline

```text
Full Baseline =
    average Actual Demand across all selected dates
```

Zero-demand dates participate in the average.

### Recent Average

```text
Recent Average =
    average of the latest min(14, selected-day count) Actual Demand values
```

Examples:

- a 7-day filter uses all seven values;
- a 14-day filter uses all fourteen values;
- a 30-day filter uses the latest fourteen values.

### Weekday Average

```text
Weekday Average(date) =
    average Actual Demand for dates with the same weekday
```

A forecast for Friday uses selected-period Friday values. If no matching weekday exists, the full baseline is used.

### Month Average

```text
Month Average(date) =
    average Actual Demand for dates in the same month
```

If the graph crosses a month boundary, each date uses its matching month group. If no matching month exists, the full baseline is used.

### Seasonal Multiplier

| Forecast month | Multiplier |
| --- | ---: |
| January or February | `0.90` |
| May or June | `1.25` |
| Other months | `1.00` |

This is a fixed rule. It is not learned from the current database.

### Trend Adjustment

```text
Trend Adjustment =
    ((Recent Average - Full Baseline) / max(1, Full Baseline)) × 0.35
```

Interpretation:

- recent average above baseline increases the forecast;
- recent average below baseline decreases the forecast;
- equal averages produce no trend adjustment.

## History Demand Line

History Demand uses the same calendar dates one year earlier.

```text
History Demand(display date) =
    reservation count on equivalent date last year
```

Example:

```text
Displayed date: June 25, 2026
History source: June 25, 2025
```

History covers the selected range plus the next three dates. This allows the future forecast to be compared with the equivalent upcoming dates last year.

Missing historical dates receive zero.

February 29 maps to February 28 when the previous year does not contain February 29.

## Midpoint Demand Line

Midpoint Demand exists only where both Actual and Forecasted Demand exist.

```text
Midpoint =
    (Actual Demand + Forecasted Demand) / 2
```

It covers only the selected range:

- 7 points for a 7-day filter;
- 14 points for a 14-day filter;
- 30 points for a 30-day filter.

It does not extend into the future because future Actual Demand is unknown.

## Forecast Insights

### Forecasted Peak

Only the three future forecast values are checked:

```text
Forecasted Peak =
    maximum forecast value among the next three days
```

The card displays the future date and value associated with that maximum.

### Expected Growth

```text
Average Actual =
    average Actual Demand across selected dates

Average Future Forecast =
    average Forecasted Demand across the next three days

Expected Growth % =
    ((Average Future Forecast - Average Actual) / Average Actual) × 100
```

If Average Actual is zero, Expected Growth is set to zero to avoid division by zero.

## Forecast Tooltip

Hovering a date displays the available line values.

For selected-period dates, the tooltip also compares Actual and Forecasted Demand.

### Actual vs Forecast Percentage

```text
Difference = Actual - Forecast

Actual vs Forecast % =
    (Difference / Forecast) × 100
```

Interpretation:

- positive: actual demand exceeded expectation;
- negative: actual demand was below expectation;
- zero: exact match.

Special zero-forecast rule:

| Forecast | Actual | Difference shown |
| ---: | ---: | ---: |
| `0` | `0` | `0%` |
| `0` | greater than `0` | `100%` |

### Expectation Hit

```text
Absolute Error = abs(Actual - Forecast)

Expectation Hit % =
    max(
        0,
        100 - (
            Absolute Error / max(1, abs(Forecast))
        ) × 100
    )
```

Interpretation:

| Value | Meaning |
| ---: | --- |
| `100%` | Exact daily match |
| `80–99%` | Close daily estimate |
| `50–79%` | Moderate difference |
| `1–49%` | Large difference |
| `0%` | Error is at least as large as the expected value |

This is a per-date closeness indicator, not a formal full-model validation metric.

## Forecast Narrative Rules

The TLDR and interpretation text is generated from threshold rules.

### Next Three-Day Guidance

```text
Future Forecast Average >= Actual Average + 2
    -> prepare for a rise

Future Forecast Average <= Actual Average - 2
    -> demand may ease

Otherwise
    -> demand is near the current baseline
```

### Growth Interpretation

| Growth value | Message category |
| ---: | --- |
| `>= 15%` | Material increase |
| `>= 0%` and `< 15%` | Slight increase |
| `< 0%` | Demand below current baseline |

### Peak Gap Interpretation

```text
Forecast Peak - Actual Peak
```

| Gap | Interpretation |
| ---: | --- |
| `>= 5` | Much higher future peak |
| `>= 2` | Moderately higher future peak |
| `<= -2` | Future peak below current peak |
| Otherwise | Peaks are close |

### Average Gap Interpretation

```text
Forecast Average - Actual Average
```

| Gap | Interpretation |
| ---: | --- |
| `>= 3` | Higher-demand cycle |
| `<= -3` | Demand tapering |
| Otherwise | Overall pattern is steady |

---

# Readiness Risk Detection

## Data Used

The current FastAPI readiness snapshot reads:

- equipment name;
- equipment state;
- operational status;
- total quantity;
- available quantity.

The range filter does not currently change the readiness score because the FastAPI readiness builder does not read reservation usage for this result.

## Availability Ratio

```text
Availability Ratio =
    Available Quantity / Total Quantity
```

If Total Quantity is zero, the ratio defaults to `1`.

## Ready or Watch Label

```text
Availability Ratio >= 0.70
    -> ready

Availability Ratio < 0.70
    -> watch
```

An item is always changed to `watch` when either state field is:

- `under maintenance`;
- `unavailable`.

## Rule-Based Risk Score

### Availability Score

| Availability ratio | Score added |
| ---: | ---: |
| `<= 0.20` | `+3` |
| `> 0.20` and `<= 0.45` | `+4` |
| `> 0.45` and `<= 0.70` | `+2` |
| `> 0.70` | `+0` |

The `0.20–0.45` range intentionally receives the largest current stock score.

### Inactive State Score

```text
Under maintenance or unavailable
    -> +3
```

### Risk Band Assignment

| Total score | Risk band |
| ---: | --- |
| `>= 6` | High Risk |
| `4–5` | Medium Risk |
| `2–3` | Low Risk |
| `0–1` | Very Low Risk |

## Top Risk Factors

The FastAPI result counts:

- Low stock pressure;
- Inactive availability state;
- High usage frequency;
- Overdue release linkage.

In the current readiness implementation:

- low stock pressure can increase;
- inactive availability can increase;
- high usage frequency remains zero;
- overdue release linkage remains zero.

The factors are sorted by count. Because all four labels are always returned, zero-count factors can still appear.

## High-Risk Equipment

Only equipment scoring at least six is included.

The page displays up to five high-risk item names in the High Risk doughnut tooltip.

## Safe Rate

```text
Safe Equipment =
    Low Risk count + Very Low Risk count

Safe Rate =
    Safe Equipment / Total Equipment × 100
```

The safe rate is displayed in the center of the doughnut chart.

## Readiness Narrative

The frontend finds the largest risk band and reports it as the dominant condition.

```text
Attention Count =
    High Risk count + Medium Risk count
```

If Attention Count is greater than zero, the interpretation recommends reviewing High- and Medium-Risk equipment first.

---

# Resource Allocation Optimization

## Pending Request Selection

The allocation snapshot selects all reservations whose status is:

- `pending`;
- `pending review`.

This pending list is not currently restricted to the selected filter date.

Requests are ordered by:

1. submission timestamp;
2. event date.

## Greedy Allocation Rule

The current implementation is a sequential greedy allocator.

Initial remaining capacity:

```text
Remaining Capacity(equipment) =
    max(0, available quantity)
```

For each pending request and requested item:

1. find equipment whose name matches case-insensitively;
2. skip equipment with no remaining capacity;
3. allocate the smaller of requested quantity and remaining capacity;
4. subtract the allocated amount from remaining capacity;
5. continue to the next request.

```text
Allocated Quantity =
    min(Requested Quantity, Remaining Capacity)
```

Earlier requests can consume inventory before later requests because the process is sequential.

## Allocation Status

```text
Allocated total < reservation requested quantity
    -> partial

Otherwise
    -> allocated
```

This is not currently a globally optimized binary solution. It does not search all possible assignments for the best objective.

## Optimization Metrics

The FastAPI allocation payload is converted into four frontend metrics.

### Conflict Reduction

Current stored/FastAPI adapter value:

```text
Conflict Reduction = 0
```

The note is `stored scenario`. The current FastAPI allocation result does not calculate scheduling-conflict reduction.

Frontend interpretation:

| Value | Meaning |
| ---: | --- |
| `>= 10` | Strong improvement |
| `>= 0` and `< 10` | Flat or slightly positive |
| `< 0` | Conflict handling is slipping |

### Equipment Utilization Delta

The frontend averages the top category utilization values:

```text
Current Utilization Average =
    average current utilization category values

Previous Utilization Average =
    average previous-year utilization category values

Equipment Utilization Metric =
    Current Average - Previous Average
```

The result is rounded to one decimal place.

Frontend interpretation:

| Value | Meaning |
| ---: | --- |
| `>= 10` | Utilization trending up |
| `>= 0` and `< 10` | Utilization steady |
| `< 0` | Utilization falling |

### Constraint Satisfaction

```text
Fulfilled Count =
    allocation plans with status allocated

Pending Count =
    number of pending or pending-review requests

Constraint Satisfaction =
    Fulfilled Count / (Fulfilled Count + Pending Count) × 100
```

This calculation uses the adapter's current formula. Because pending requests are also represented in the allocation plan, this is not the percentage of plans marked allocated.

Frontend interpretation:

| Value | Meaning |
| ---: | --- |
| `>= 95%` | Very high |
| `>= 80%` and `< 95%` | Acceptable |
| `< 80%` | Weak |

### Unassigned Requests

```text
Unassigned Requests =
    Pending Request Count
```

Frontend interpretation:

| Value | Meaning |
| ---: | --- |
| `<= 5` | Low |
| `6–20` | Moderate |
| `> 20` | High |

---

# Equipment Utilization Overview

## Current-Period Usage

Equipment usage is based on reservation `event_date_time`, not submission date.

Included reservations:

- event date falls inside the selected filter;
- status is not `cancelled`;
- status is not `rejected`.

For each equipment line item:

```text
Usage Count += max(1, requested item quantity)
```

## Category Utilization

For every category:

```text
Category Requested Quantity =
    sum of current-range usage for equipment in category

Category Inventory =
    sum of total quantity for equipment in category

Category Utilization % =
    min(
        100,
        Category Requested Quantity / Category Inventory × 100
    )
```

The value is rounded to one decimal place.

The percentage is capped at 100 even if requested quantity exceeds inventory.

Only the top five categories are returned, sorted by:

1. utilization descending;
2. category name ascending.

## Comparison Bars

The comparison series uses the same calendar dates one year earlier.

```text
Current Period:
    selected start date through selected end date

Comparison Period:
    selected start date minus one year
    through selected end date minus one year
```

The chart displays the union of current and historical category names. It ranks categories by the larger of the two values and shows the top five.

## Utilization Tooltip

The tooltip displays:

```text
Current Period: value%
Last Year Same Days: value%
```

## Utilization Narrative Thresholds

### Leading Category

| Highest utilization | Interpretation |
| ---: | --- |
| `>= 75%` | Very active |
| `>= 40%` | Moderately active |
| `< 40%` | Current stock is likely adequate |

### Spread

```text
Spread =
    highest visible utilization - lowest visible utilization
```

| Spread | Interpretation |
| ---: | --- |
| `>= 30` | Wide and uneven demand |
| `>= 10` and `< 30` | Moderate difference |
| `< 10` | Fairly balanced |

### Top Average

```text
Top Average =
    average of the visible top category values
```

`>= 50%` recommends protecting the most-used group first.

### Three-Day Intensity Message

```text
Top Three Average =
    average of the highest three visible category values
```

| Average | Narrative intensity |
| ---: | --- |
| `>= 60%` | High |
| `>= 35%` and `< 60%` | Moderate |
| `< 35%` | Light |

This message is a rule-based interpretation of category utilization. It is not calculated from the three-day demand forecast.

---

# Top Equipment Trends

## Top Frequently Used Equipment

For each equipment item:

```text
Times Used =
    sum of requested quantities in valid selected-range reservations
```

The backend builds up to ten items sorted by:

1. usage count descending;
2. equipment name ascending for ties.

The page displays only the first five.

The backend also calculates:

```text
Equipment Rate =
    min(100, Usage Count / Total Quantity × 100)
```

The table currently displays Times Used, not the rate.

## Top Possible Borrowed Equipment

Only equipment with current selected-range usage greater than zero is eligible.

The FastAPI rule-based candidate score is:

```text
Candidate Score =
    Previous-Year Usage × 1.4
    + Current Usage × 0.8
```

Candidates are sorted by:

1. score descending;
2. equipment name ascending.

The first five are displayed.

The Times Used column displays current-range usage, even when previous-year demand caused the item to rank higher.

### Candidate Reason

```text
Previous-Year Usage > Current Usage
    -> seasonal-demand reason

Otherwise, Current Usage > 0
    -> keep-prepared reason
```

Examples:

```text
Used 15 times on the same dates last year,
so prepare it for seasonal demand.
```

```text
Already used 12 times in this range,
so keep it prepared.
```

---

# System Summary

## Total Equipment

```text
Total Equipment =
    number of equipment records
```

This counts equipment records, not total inventory units.

## Active Reservations

Current FastAPI allocation summary:

```text
Active Reservations =
    number of allocation-plan entries
```

Because the allocation plan is built from pending and pending-review reservations, this currently represents requests considered by the allocator rather than all approved/deployed reservations.

## Pending Requests

```text
Pending Requests =
    number of reservations with status pending or pending review
```

This count currently includes all such reservations and is not restricted by the filter.

## Completed This Period

```text
Completed This Period =
    count of reservations where:
        event date is inside the selected range
        and status is completed or returned
```

## Generated At

The generated timestamp comes from the FastAPI range-analysis response.

---

# Scenario and Clean-Data Rules

The **Run Analytics Now** button uses a different path from normal filtering:

```text
Vue POST /api/v1/analytics/trigger-run
    -> Symfony
        -> FastAPI POST /analytics/run-daily-check
```

This path can modify database data.

## Clean Data

The current `clean_data` action:

1. deletes analytics result records;
2. deletes analytics run records;
3. deletes the active daily analytics configuration;
4. deletes all reservations;
5. runs analytics against the remaining equipment inventory.

Despite its UI description, it currently clears reservation data rather than creating a balanced neutral reservation dataset.

## Demand Scenarios

The scenario choices are:

- high demand last year, low this semester;
- high demand last year, high this semester;
- low demand last year, low this semester;
- low demand last year, high this semester;
- mixed.

For these scenarios, FastAPI:

1. clears previous analytics results and runs;
2. clears all reservations;
3. executes the selected SQL seed file;
4. commits the seeded reservation dataset;
5. runs forecast, readiness, and allocation calculations;
6. stores the new analytics run and result payloads.

Scenario runs use the currently selected 7-, 14-, or 30-day date range for the generated analytics response.

## Stored Analytics Runs

A scenario or scheduled run inserts:

- one row into `analytics_runs`;
- three rows into `analytics_results`:
  - forecast;
  - readiness;
  - allocation.

Normal filter refreshes do not insert these records.

---

# PDF Report

The Generate PDF Report button captures the visible report surface with `html2canvas` and inserts the rendered image into a PDF using `jsPDF`.

The PDF contains the currently displayed:

- selected date range;
- forecast chart;
- risk chart;
- optimization metrics;
- utilization chart;
- equipment trend tables;
- system summary.

It does not independently recalculate analytics.

---

# Rule-Based Limitations

The current results should be interpreted with these limitations:

1. Forecasting uses fixed weights and fixed month multipliers.
2. Forecasting has no confidence interval.
3. Readiness does not currently use reservation frequency or overdue linkage in FastAPI.
4. Risk factor labels can appear even when their count is zero.
5. Allocation is greedy and order-dependent.
6. Pending allocation is not currently filtered by the selected date range.
7. Constraint Satisfaction uses a simplified adapter formula.
8. Utilization is capped at 100%.
9. Possible-borrowed ranking requires current usage greater than zero.
10. Narrative text uses thresholds and does not represent a separate learned model.

---

# Implementation References

| Responsibility | File |
| --- | --- |
| Page layout, filters, midpoint, scenarios, PDF | `frontend/src/pages/admin/ReportsAnalytics.vue` |
| FastAPI range API client | `frontend/src/modules/dashboard/services/adminAnalyticsApi.js` |
| FastAPI payload normalization | `frontend/src/pages/admin/services/reportsAnalyticsDataAdapter.js` |
| Forecast, risk, utilization narratives | `frontend/src/pages/admin/services/reportsAnalyticsNarrativeService.js` |
| Chart datasets and tooltips | `frontend/src/pages/admin/services/reportsAnalyticsChartRenderer.js` |
| Filter date calculation | `frontend/src/pages/admin/adminAnalyticsHelpers.js` |
| Symfony analytics routes | `backend/src/Domain/Analytics/Controller/AnalyticsController.php` |
| FastAPI endpoints | `analytics-service/app/main.py` |
| Analytics execution and storage | `analytics-service/app/analytics_runner.py` |
| Forecast rules | `analytics-service/app/forecast_snapshot.py` |
| Readiness rules | `analytics-service/app/readiness_snapshot.py` |
| Allocation and equipment trend rules | `analytics-service/app/allocation_snapshot.py` |
| Scenario and clean-data behavior | `analytics-service/app/scenario_preparer.py` |

