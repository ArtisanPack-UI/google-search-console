---
title: GET /performance
---

# `GET /performance`

Returns the performance overview: totals across the range plus a daily trend series.

- **Route name**: `google-search-console.performance`
- **Middleware**: `web`, `auth`
- **Controller**: `ArtisanPackUI\GoogleSearchConsole\Http\Controllers\PerformanceOverviewController`
- **Consumed by**: [[Components/React|`<PerformanceCard />` (React)]] and [[Components/Vue|`<PerformanceCard />` (Vue)]] via `resources/js/shared/performance.ts`.

## Query parameters

| Parameter | Type | Default | Clamp | Notes |
|---|---|---|---|---|
| `days` | `int` | `28` | `1..DateRange::MAX_DAYS` | Any non-scalar → `28`. |

## Behavior

Two `searchAnalytics.query` calls under the hood:

1. **Totals** — no dimensions, aggregated across the range.
2. **Trend** — dimension `date`, one row per day.

The controller runs both via `PerformanceOverviewFetcher::fetch()` and returns the resulting `PerformanceOverviewData::toArray()`.

## Successful response

```json
{
    "range": {
        "startDate": "2026-01-01",
        "endDate":   "2026-01-28"
    },
    "totals": {
        "clicks":      1234,
        "impressions": 45678,
        "ctr":         0.027,
        "position":    12.3
    },
    "trend": [
        { "date": "2026-01-01", "clicks": 42, "impressions": 500, "ctr": 0.084, "position": 3.2 },
        { "date": "2026-01-02", "clicks": 51, "impressions": 620, "ctr": 0.082, "position": 3.1 }
    ],
    "hasData": true
}
```

### `hasData`

`true` when either call returned at least one row. `false` when both calls returned an empty rows array — a fresh Search Console property, a range in the last 2–3 finalising days, or an account with genuine zero traffic. The distinction matters because the API returns `{}` (no `rows` key) rather than a row of zeros, which would otherwise fold into `totals = { clicks: 0, impressions: 0, ... }` and misrepresent as "0 clicks this week". Components render an explicit empty state instead.

### `range`

Echoes the resolved date range so the client doesn't have to compute it. `startDate` is inclusive; `endDate` is inclusive.

## Error responses

Standard shape (see [[HTTP Endpoints#Error response shapes]]):

- `401 unauthenticated` — no signed-in user.
- `409 not_connected` — user has no connected Google account.
- `501 base_not_installed` — base package missing.
- `502 reporting_error` — the API call failed.

## Example calls

```bash
# Last 28 days (default)
curl 'https://your-app.test/google-search-console/performance' \
    --cookie 'laravel_session=...'

# Last 90 days
curl 'https://your-app.test/google-search-console/performance?days=90' \
    --cookie 'laravel_session=...'

# Invalid range (non-numeric)
curl 'https://your-app.test/google-search-console/performance?days=abc' \
    --cookie 'laravel_session=...'
# → falls back to days=28
```

## Testing

```php
Http::fakeSequence()
    ->push( [ 'rows' => [ [ 'keys' => [], 'clicks' => 42, 'impressions' => 500, 'ctr' => 0.084, 'position' => 3.2 ] ] ], 200 )
    ->push( [ 'rows' => [ [ 'keys' => [ '2026-01-01' ], 'clicks' => 42, 'impressions' => 500, 'ctr' => 0.084, 'position' => 3.2 ] ] ], 200 );

$this->actingAs( $user )
    ->getJson( '/google-search-console/performance?days=7' )
    ->assertOk()
    ->assertJsonPath( 'hasData', true )
    ->assertJsonPath( 'totals.clicks', 42.0 );
```
