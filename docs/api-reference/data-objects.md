---
title: Data Objects
---

# Data Objects

The three DTOs the shipped [[API Reference/Fetchers|fetchers]] return. All three carry a `toArray()` method that serialises to exactly the shape the [[HTTP Endpoints|matching JSON endpoint]] returns.

## `PerformanceOverviewData`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewData`

```php
final class PerformanceOverviewData
{
    public function __construct(
        public readonly DateRange $range,
        public readonly array $totals,   // ['clicks' => float, 'impressions' => float, 'ctr' => float, 'position' => float]
        public readonly array $trend,    // list<array{date: string, clicks: float, impressions: float, ctr: float, position: float}>
        public readonly bool $hasData,
    );

    public function toArray(): array;
}
```

`hasData` is `false` when both the totals and trend queries returned no rows — a fresh property, a range in the last 2–3 finalising days, or an account with genuine zero traffic. `toArray()` includes it as `has_data`.

## `TopQueriesData`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\TopQueriesData`

```php
final class TopQueriesData
{
    public function __construct(
        public readonly DateRange $range,
        public readonly array $rows,     // list<array{query: string, clicks: float, impressions: float, ctr: float, position: float}>
    );

    public function toArray(): array;
}
```

Rows are Google-sorted (clicks descending). Empty `rows` means no data.

## `TopPagesData`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\TopPagesData`

```php
final class TopPagesData
{
    public function __construct(
        public readonly DateRange $range,
        public readonly array $rows,     // list<array{page: string, clicks: float, impressions: float, ctr: float, position: float}>
    );

    public function toArray(): array;
}
```

Same shape as `TopQueriesData`, rows keyed by `page` instead of `query`.

## Why plain arrays instead of row DTOs?

The row-level shape (`{ clicks, impressions, ctr, position }`) is Google's, matches the JSON payload, and is directly consumed by the shipped views (Blade, React, Vue) without transformation. Wrapping each row in a per-row class would add allocation cost with no runtime benefit.

## See also

- [[API Reference/Fetchers]] — return these DTOs.
- [[HTTP Endpoints]] — JSON endpoints return `toArray()` output.
