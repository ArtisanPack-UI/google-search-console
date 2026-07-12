---
title: SearchAnalyticsRequest
---

# `SearchAnalyticsRequest`

Immutable value object describing a single `searchAnalytics.query` request. FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsRequest`.

## Constants

- `MAX_ROW_LIMIT = 25000` — Google's per-request cap. Callers who need more should paginate via `startRow`.

## Constructor

```php
public function __construct(
    public readonly DateRange $dateRange,
    public readonly array $dimensions = [],
    public readonly array $dimensionFilterGroups = [],
    public readonly ?int $rowLimit = null,
    public readonly ?int $startRow = null,
    public readonly ?string $searchType = null,
    public readonly ?string $dataState = null,
)
```

All properties are `readonly` — construct once, pass around.

## Property reference

| Property | Type | Google API field | Notes |
|---|---|---|---|
| `dateRange` | `DateRange` | `startDate`, `endDate` | Required. |
| `dimensions` | `list<string>` | `dimensions` | `query`, `page`, `country`, `device`, `searchAppearance`, `date`. Zero or more. |
| `dimensionFilterGroups` | `list<array<string, mixed>>` | `dimensionFilterGroups` | Raw shape per the Search Console API spec — see below. |
| `rowLimit` | `?int` | `rowLimit` | Capped to `MAX_ROW_LIMIT` in `toApiPayload()`. |
| `startRow` | `?int` | `startRow` | Zero-indexed. Use for pagination. |
| `searchType` | `?string` | `type` | `web` (default), `image`, `video`, `news`, `discover`, `googleNews`. |
| `dataState` | `?string` | `dataState` | `all` (default, includes unfinalised data) or `final`. |

## Methods

### `toApiPayload(): array`

Serialises to the JSON body Google expects:

```php
[
    'startDate'             => $this->dateRange->startDate,
    'endDate'               => $this->dateRange->endDate,
    'dimensions'            => $this->dimensions,             // only if non-empty
    'dimensionFilterGroups' => $this->dimensionFilterGroups,  // only if non-empty
    'rowLimit'              => min( $this->rowLimit, MAX_ROW_LIMIT ),  // only if not null
    'startRow'              => $this->startRow,               // only if not null
    'type'                  => $this->searchType,             // only if not null
    'dataState'             => $this->dataState,              // only if not null
]
```

The client hashes this array to build the cache key, so identical requests (same dimensions, same range, same filters) collapse to a single cache entry.

## Ordering

Google's `searchAnalytics.query` does **NOT** accept an `orderBys` field — rows always come back sorted by `clicks` descending. Callers who need a different order should sort the parsed `SearchAnalyticsResponse::rows()` client-side.

## `dimensionFilterGroups` shape

Google's spec: each group is `{ groupType: "and", filters: [...] }` where `groupType` defaults to `and`. Each filter is `{ dimension: "...", operator: "...", expression: "..." }`.

Example — top queries in the US, excluding a specific page:

```php
new SearchAnalyticsRequest(
    dateRange:  DateRange::lastDays( 28 ),
    dimensions: [ 'query' ],
    dimensionFilterGroups: [
        [
            'filters' => [
                [ 'dimension' => 'country', 'operator' => 'equals',        'expression' => 'usa' ],
                [ 'dimension' => 'page',    'operator' => 'notContains',   'expression' => '/admin' ],
            ],
        ],
    ],
    rowLimit: 500,
);
```

Full operator list at Google's [Search Analytics API reference](https://developers.google.com/webmaster-tools/v1/searchanalytics/query).

## See also

- [API Reference/Search Analytics Client](API-Reference-Search-Analytics-Client) — accepts this DTO.
- [API Reference/Date Range](API-Reference-Date-Range) — the `dateRange` property type.
- [Reporting/Search Analytics Client](Reporting-Search-Analytics-Client) — usage patterns.
