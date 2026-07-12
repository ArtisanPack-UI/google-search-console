---
title: Fetchers
---

# Fetchers

Full usage in [[Reporting/Fetchers]]. This page is the class-level reference only.

## `PerformanceOverviewFetcher`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher`

```php
public function __construct( protected SearchAnalyticsClient $client )

public function fetch(
    GoogleConnection $connection,
    DateRange $range,
    ?string $siteUrl = null,
): PerformanceOverviewData
```

Runs two `searchAnalytics.query` calls (totals + daily trend). Returns [[API Reference/Data Objects|`PerformanceOverviewData`]].

## `TopQueriesFetcher`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\TopQueriesFetcher`

Constant: `DEFAULT_LIMIT = 50`.

```php
public function __construct( protected SearchAnalyticsClient $client )

public function fetch(
    GoogleConnection $connection,
    DateRange $range,
    ?string $siteUrl = null,
    ?int $limit = null,
): TopQueriesData
```

Runs one `searchAnalytics.query` with dimension `query`, row limit `$limit ?? self::DEFAULT_LIMIT` (clamped by the request DTO to `1..SearchAnalyticsRequest::MAX_ROW_LIMIT`). Returns [[API Reference/Data Objects|`TopQueriesData`]].

## `TopPagesFetcher`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\TopPagesFetcher`

Constant: `DEFAULT_LIMIT = 50`.

```php
public function __construct( protected SearchAnalyticsClient $client )

public function fetch(
    GoogleConnection $connection,
    DateRange $range,
    ?string $siteUrl = null,
    ?int $limit = null,
): TopPagesData
```

Same shape as `TopQueriesFetcher`, with dimension `page`. Returns [[API Reference/Data Objects|`TopPagesData`]].

## See also

- [[Reporting/Fetchers]] — usage + examples + writing your own.
- [[API Reference/Data Objects]] — the returned DTOs.
- [[API Reference/Search Analytics Client]] — the shared dependency.
