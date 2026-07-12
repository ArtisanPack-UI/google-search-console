---
title: GET /top-queries
---

# `GET /top-queries`

Returns the top search queries by clicks for a rolling range.

- **Route name**: `google-search-console.top-queries`
- **Middleware**: `web`, `auth`
- **Controller**: `ArtisanPackUI\GoogleSearchConsole\Http\Controllers\TopQueriesController`
- **Consumed by**: [`<TopQueriesTable />` (React)](Components-React) and [`<TopQueriesTable />` (Vue)](Components-Vue) via `resources/js/shared/top-queries.ts`.

## Query parameters

| Parameter | Type | Default | Clamp | Notes |
|---|---|---|---|---|
| `days` | `int` | `28` | `1..DateRange::MAX_DAYS` | Any non-scalar → `28`. |
| `limit` | `int` | `TopQueriesFetcher::DEFAULT_LIMIT` (`50`) | `1..1000` | Number of rows to pull from Google. |

## Behavior

One `searchAnalytics.query` call with dimension `query`, row limit `limit`. Rows come back sorted by clicks descending — the API doesn't accept an `orderBys` field, so the returned order is what Google chose. The React and Vue components sort client-side after the fetch; the shipped Livewire component sorts and paginates over the visible page in-memory.

## Successful response

```json
{
    "range": {
        "startDate": "2026-01-01",
        "endDate":   "2026-01-28"
    },
    "rows": [
        { "query": "buy shoes",     "clicks": 50, "impressions": 200, "ctr": 0.25, "position": 2 },
        { "query": "cheap shoes",   "clicks": 20, "impressions": 100, "ctr": 0.2,  "position": 4 },
        { "query": "running shoes", "clicks": 15, "impressions": 90,  "ctr": 0.16, "position": 5 }
    ]
}
```

An empty `rows` array is a valid response — it means Google returned zero queries in the range. The components render an empty state.

## Error responses

Standard shape (see [HTTP Endpoints](HTTP-Endpoints#error-response-shapes)):

- `401 unauthenticated`
- `409 not_connected`
- `501 base_not_installed`
- `502 reporting_error`

## Example calls

```bash
# Default: last 28 days, top 50 queries
curl 'https://your-app.test/google-search-console/top-queries' \
    --cookie 'laravel_session=...'

# Last 90 days, top 200 queries
curl 'https://your-app.test/google-search-console/top-queries?days=90&limit=200' \
    --cookie 'laravel_session=...'

# Way over cap — clamps to 1000
curl 'https://your-app.test/google-search-console/top-queries?limit=999999' \
    --cookie 'laravel_session=...'
```

## Testing

```php
Http::fake( [
    '*' => Http::response( [
        'rows' => [
            [ 'keys' => [ 'high' ], 'clicks' => 50, 'impressions' => 200, 'ctr' => 0.25, 'position' => 2 ],
            [ 'keys' => [ 'mid'  ], 'clicks' => 20, 'impressions' => 100, 'ctr' => 0.2,  'position' => 4 ],
        ],
    ], 200 ),
] );

$this->actingAs( $user )
    ->getJson( '/google-search-console/top-queries?days=7&limit=25' )
    ->assertOk()
    ->assertJsonPath( 'rows.0.query', 'high' )
    ->assertJsonPath( 'rows.0.clicks', 50.0 );
```
