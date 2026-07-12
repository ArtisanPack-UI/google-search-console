---
title: Fetchers
---

# Fetchers

Higher-level helpers that build the exact `SearchAnalyticsRequest` the shipped UIs need and return typed DTOs. Every fetcher is a thin wrapper — no state of its own, no dependencies beyond a `SearchAnalyticsClient` instance.

Three fetchers ship:

| Fetcher | Runs | Returns |
|---|---|---|
| `PerformanceOverviewFetcher` | Two API calls (totals + daily trend) | `PerformanceOverviewData` |
| `TopQueriesFetcher` | One API call (dimension `query`) | `TopQueriesData` |
| `TopPagesFetcher` | One API call (dimension `page`) | `TopPagesData` |

## `PerformanceOverviewFetcher`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher`

### `fetch( GoogleConnection $connection, DateRange $range, ?string $siteUrl = null ): PerformanceOverviewData`

Two `searchAnalytics.query` calls:

1. **Totals**: no dimensions, aggregated across the range.
2. **Trend**: dimension `date`, one row per day, sorted ascending after the response is parsed.

The DTO exposes:

- `totals: array{clicks: float, impressions: float, ctr: float, position: float}`
- `trend: list<array{date: string, clicks: float, impressions: float, ctr: float, position: float}>`
- `hasData: bool` — `false` when both calls returned zero rows (fresh property, last 2–3 finalising days, etc.).
- `toArray(): array` — serializes to the shape the [`/performance`](HTTP-Endpoints-Performance) endpoint returns.

Example:

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher;

$fetcher = new PerformanceOverviewFetcher( googleSearchConsole()->client() );
$data    = $fetcher->fetch( $connection, DateRange::lastDays( 28 ) );

if ( ! $data->hasData ) {
    return view( 'dashboard.empty' );
}

echo number_format( $data->totals[ 'clicks' ] ) . ' clicks over ' . count( $data->trend ) . ' days';
```

## `TopQueriesFetcher`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\TopQueriesFetcher`

Constant: `DEFAULT_LIMIT = 50`.

### `fetch( GoogleConnection $connection, DateRange $range, ?string $siteUrl = null, ?int $limit = null ): TopQueriesData`

One `searchAnalytics.query` call with dimension `query`, row limit `$limit ?? self::DEFAULT_LIMIT` (clamped by the request DTO to `1..SearchAnalyticsRequest::MAX_ROW_LIMIT`, which is `25000`).

The DTO exposes:

- `rows: list<array{query: string, clicks: float, impressions: float, ctr: float, position: float}>`
- `toArray(): array` — matches the [`/top-queries`](HTTP-Endpoints-Top-Queries) endpoint shape.

Rows come back sorted by clicks descending — Google's default and unchangeable (the API doesn't accept an `orderBys`).

Example:

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\TopQueriesFetcher;

$fetcher = new TopQueriesFetcher( googleSearchConsole()->client() );
$data    = $fetcher->fetch( $connection, DateRange::lastDays( 90 ), null, 200 );

foreach ( $data->rows as $row ) {
    echo "{$row['query']}: {$row['clicks']} clicks, {$row['impressions']} impressions\n";
}
```

## `TopPagesFetcher`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\TopPagesFetcher`

Constant: `DEFAULT_LIMIT = 50`.

### `fetch( GoogleConnection $connection, DateRange $range, ?string $siteUrl = null, ?int $limit = null ): TopPagesData`

Same shape as `TopQueriesFetcher`, but with dimension `page`. Rows carry a `page` key (the full URL) instead of a `query` key.

Example:

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\TopPagesFetcher;

$fetcher = new TopPagesFetcher( googleSearchConsole()->client() );
$data    = $fetcher->fetch( $connection, DateRange::lastDays( 28 ) );

foreach ( $data->rows as $row ) {
    echo "{$row['page']}: {$row['clicks']} clicks\n";
}
```

## When to write your own fetcher

The three ship because they back the three shipped UIs. If your report is similar but different — e.g., "top queries filtered by country US" — you have three options:

1. **Skip the fetcher.** Call `SearchAnalyticsClient::query()` directly with your own `SearchAnalyticsRequest`. Simplest for one-off reports.
2. **Extend one of the shipped fetchers.** They're plain classes; subclass and override the `fetch()` body if 80% overlaps.
3. **Write your own.** Copy `TopQueriesFetcher` as a template — it's ~30 lines.

Example custom fetcher:

```php
namespace App\Reporting;

use ArtisanPackUI\Google\Models\GoogleConnection;
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsRequest;

class TopQueriesByCountryFetcher
{
    public function __construct( protected SearchAnalyticsClient $client ) {}

    public function fetch( GoogleConnection $connection, DateRange $range, string $country ): array
    {
        $response = $this->client->query(
            new SearchAnalyticsRequest(
                dateRange:  $range,
                dimensions: [ 'query' ],
                dimensionFilterGroups: [
                    [
                        'filters' => [
                            [ 'dimension' => 'country', 'operator' => 'equals', 'expression' => $country ],
                        ],
                    ],
                ],
                rowLimit:   100,
            ),
            $connection,
        );

        return $response->rows();
    }
}
```

Bind and inject as you would any Laravel service.

## See also

- [API Reference/Fetchers](API-Reference-Fetchers) — full class signatures.
- [Reporting/Date Range](Reporting-Date-Range) — the `DateRange` value object.
- [Reporting/Search Analytics Client](Reporting-Search-Analytics-Client) — the underlying client.
