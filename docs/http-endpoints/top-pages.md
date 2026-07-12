---
title: GET /top-pages
---

# `GET /top-pages`

Returns the top pages by clicks for a rolling range.

- **Route name**: `google-search-console.top-pages`
- **Middleware**: `web`, `auth`
- **Controller**: `ArtisanPackUI\GoogleSearchConsole\Http\Controllers\TopPagesController`
- **Consumed by**: [[Components/React|`<TopPagesTable />` (React)]] and [[Components/Vue|`<TopPagesTable />` (Vue)]] via `resources/js/shared/top-pages.ts`.

## Query parameters

| Parameter | Type | Default | Clamp | Notes |
|---|---|---|---|---|
| `days` | `int` | `28` | `1..DateRange::MAX_DAYS` | Any non-scalar → `28`. |
| `limit` | `int` | `TopPagesFetcher::DEFAULT_LIMIT` (`50`) | `1..1000` | Number of rows to pull from Google. |

## Behavior

One `searchAnalytics.query` call with dimension `page`, row limit `limit`. Rows come back sorted by clicks descending. Same client-side sort / pagination story as top-queries.

## Successful response

```json
{
    "range": {
        "startDate": "2026-01-01",
        "endDate":   "2026-01-28"
    },
    "rows": [
        { "page": "https://example.com/pricing", "clicks": 33, "impressions": 300, "ctr": 0.11, "position": 4 },
        { "page": "https://example.com/blog",    "clicks": 15, "impressions": 150, "ctr": 0.1,  "position": 6 }
    ]
}
```

The `page` values are absolute URLs — Google returns them verbatim from the property. For URL-property queries this includes the property's scheme + host; for Domain-property queries the URLs cover every subdomain the domain owns.

An empty `rows` array is valid — the components render an empty state.

## Error responses

Standard shape (see [[HTTP Endpoints#Error response shapes]]):

- `401 unauthenticated`
- `409 not_connected`
- `501 base_not_installed`
- `502 reporting_error`

## Example calls

```bash
# Default: last 28 days, top 50 pages
curl 'https://your-app.test/google-search-console/top-pages' \
    --cookie 'laravel_session=...'

# Last 90 days, top 100 pages
curl 'https://your-app.test/google-search-console/top-pages?days=90&limit=100' \
    --cookie 'laravel_session=...'
```

## Testing

```php
Http::fake( [
    '*' => Http::response( [
        'rows' => [
            [ 'keys' => [ '/high-pageview' ], 'clicks' => 33, 'impressions' => 300, 'ctr' => 0.11, 'position' => 4 ],
            [ 'keys' => [ '/mid-pageview'  ], 'clicks' => 15, 'impressions' => 150, 'ctr' => 0.1,  'position' => 6 ],
        ],
    ], 200 ),
] );

$this->actingAs( $user )
    ->getJson( '/google-search-console/top-pages?days=7&limit=25' )
    ->assertOk()
    ->assertJsonPath( 'rows.0.page', '/high-pageview' )
    ->assertJsonPath( 'rows.0.clicks', 33.0 );
```
