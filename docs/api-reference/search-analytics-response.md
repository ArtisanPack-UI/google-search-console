---
title: SearchAnalyticsResponse
---

# `SearchAnalyticsResponse`

Read-only wrapper over the raw JSON body Google's `searchAnalytics.query` endpoint returns. FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsResponse`.

## Constructor

```php
public function __construct(
    public readonly array $raw,
    public readonly array $dimensions = [],
)
```

- `$raw` — the JSON body Google returned, decoded to an array.
- `$dimensions` — the dimension names, in the order they appear in each row's `keys`. Search Console does NOT echo dimension headers, so callers must pass what they requested. `SearchAnalyticsClient::query()` passes them automatically.

## Public methods

### `responseAggregationType(): string`

The response type Google reports (`web`, `image`, `video`, `news`, `discover`, `googleNews`). Empty string when the API didn't include it.

### `rows(): list<array<string, float|string>>`

Iterate the response rows as associative arrays keyed by dimension name plus the four metric fields.

For a request with `dimensions: ['query', 'page']`, a row like:

```json
{ "keys": ["buy shoes", "https://example.com/shoes"], "clicks": 42, "impressions": 500, "ctr": 0.084, "position": 3.2 }
```

becomes:

```php
[
    'query'       => 'buy shoes',
    'page'        => 'https://example.com/shoes',
    'clicks'      => 42.0,
    'impressions' => 500.0,
    'ctr'         => 0.084,
    'position'    => 3.2,
]
```

Missing metric values default to `0.0`. Missing `keys` values default to empty strings.

An empty response body or a response without a `rows` key returns `[]` — the null-object case.

### `totalFor( string $metric ): float`

Sum the values of a single metric across every row.

```php
$totalClicks = $response->totalFor( 'clicks' );
```

Useful when the request had no dimensions but you still want typed access to a single metric. Non-numeric values are skipped.

## What it does not do

- **No sorting.** Rows are returned in the order Google gave them (always clicks descending).
- **No pagination helpers.** If Google paginated, you'll need to issue follow-up requests with `startRow` yourself.
- **No filter application.** The response is what the API returned; if you built the request without filters, you'll get everything.

## See also

- [[API Reference/Search Analytics Client]] — `query()` returns this.
- [[API Reference/Search Analytics Request]] — building the request.
