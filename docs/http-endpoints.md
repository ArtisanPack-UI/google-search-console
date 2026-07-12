---
title: HTTP Endpoints
---

# HTTP Endpoints

The package mounts three JSON endpoints under `google-search-console.routes.prefix` (default `/google-search-console`) with `web` + `auth` middleware. They back the [[Components/React|React]] and [[Components/Vue|Vue]] components; the Livewire components fetch server-side and skip this layer entirely.

Set `google-search-console.routes.enabled = false` in the config to skip registration completely — the correct choice for headless / API-only apps.

## Route table

| Method | URI | Route name |
|---|---|---|
| GET | `/google-search-console/performance?days=28` | `google-search-console.performance` |
| GET | `/google-search-console/top-queries?days=28&limit=50` | `google-search-console.top-queries` |
| GET | `/google-search-console/top-pages?days=28&limit=50` | `google-search-console.top-pages` |

Sub-pages:

- [[HTTP Endpoints/Performance|GET /performance]] — performance card payload.
- [[HTTP Endpoints/Top Queries|GET /top-queries]] — top queries payload.
- [[HTTP Endpoints/Top Pages|GET /top-pages]] — top pages payload.

## Shared behavior

All three endpoints:

- Require authentication. Unauthenticated requests get `401` with `{ "error": "unauthenticated", "message": "..." }`.
- Require the base package. If `BaseInstalled::check()` returns false, they respond with `501` and `{ "error": "base_not_installed", "message": "...", "baseInstalled": false }`.
- Require a connected Google account. If `GoogleConnectionResolver::forUser()` returns null, they respond with `409` and `{ "error": "not_connected", "message": "..." }`.
- Wrap Search Console API failures in `502` responses with `{ "error": "reporting_error", "message": "..." }`.
- **Ignore any user-supplied `?site_url` query string.** The site URL comes from `config('google-search-console.reporting.site_url')` only. This is deliberate — accepting a caller-supplied override would let any authenticated user query any property the shared Google account owns. See [[Reporting#Multi-tenant apps]] for the multi-tenant pattern.

## Query parameters

| Parameter | Type | Default | Clamp | Notes |
|---|---|---|---|---|
| `days` | `int` | `28` | `1..DateRange::MAX_DAYS` (16 months) | Any non-scalar → `28`. |
| `limit` | `int` | `50` | `1..1000` | Only on `/top-queries` and `/top-pages`. Ignored on `/performance`. |

## Successful response shapes

### `GET /performance`

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
        { "date": "2026-01-02", "clicks": 51, "impressions": 620, "ctr": 0.082, "position": 3.1 },
        ...
    ],
    "hasData": true
}
```

`hasData: false` means Google returned no rows for the range — a fresh property, a range in the last 2–3 days that Google is still finalising, or the account has zero clicks. Renders as an empty state in the components, not a zero-clicks card.

### `GET /top-queries`

```json
{
    "range": {
        "startDate": "2026-01-01",
        "endDate":   "2026-01-28"
    },
    "rows": [
        { "query": "buy shoes",   "clicks": 50, "impressions": 200, "ctr": 0.25, "position": 2 },
        { "query": "cheap shoes", "clicks": 20, "impressions": 100, "ctr": 0.2,  "position": 4 },
        ...
    ]
}
```

Rows are sorted by clicks descending (Google's default; `searchAnalytics.query` doesn't accept an `orderBys` field).

### `GET /top-pages`

```json
{
    "range": {
        "startDate": "2026-01-01",
        "endDate":   "2026-01-28"
    },
    "rows": [
        { "page": "https://example.com/pricing", "clicks": 33, "impressions": 300, "ctr": 0.11, "position": 4 },
        { "page": "https://example.com/blog",    "clicks": 15, "impressions": 150, "ctr": 0.1,  "position": 6 },
        ...
    ]
}
```

Same sort as top-queries.

## Error response shapes

Every error response has the same shape:

```json
{
    "error":   "<error_code>",
    "message": "<human-readable message>"
}
```

The `base_not_installed` payload adds `"baseInstalled": false` for symmetry with the Livewire components' `$baseInstalled` public property.

### Status codes and error codes

| HTTP status | `error` | When |
|---|---|---|
| `401` | `unauthenticated` | No authenticated user. The route's `auth` middleware also produces `401` before the controller runs. |
| `409` | `not_connected` | The user has no `GoogleConnection` row with `status = connected`. |
| `501` | `base_not_installed` | `artisanpack-ui/google` is not on the autoloader. Install and configure the base package. |
| `502` | `reporting_error` | The `SearchAnalyticsClient` threw a `ReportingException` — API 4xx/5xx, transport failure, or config problem. |

## Testing

Every endpoint is covered by the package's own `tests/Feature/ControllersTest.php` — see it for the reference pattern:

```php
$this->actingAs( $user )
    ->getJson( '/google-search-console/performance?days=7' )
    ->assertOk()
    ->assertJsonPath( 'totals.clicks', 42.0 );
```

Fake Google with `Http::fake()`, stub `GoogleConnectionResolver` and `SearchAnalyticsClient` with `->instance()`, and the round-trip is deterministic. Full patterns in [[Testing]].
