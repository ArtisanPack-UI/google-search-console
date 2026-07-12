---
title: DateRange
---

# `DateRange`

Immutable value object describing an inclusive `[startDate, endDate]` window. Used by every fetcher and by `SearchAnalyticsRequest` to build the `startDate` / `endDate` fields of the Search Console API payload.

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange`.

## Constructor

```php
new DateRange(
    public readonly string $startDate,
    public readonly string $endDate,
)
```

Both dates are ISO 8601 (`YYYY-MM-DD`). Inclusive on both ends — `new DateRange('2026-01-01', '2026-01-28')` covers 28 days.

## Constants

- `DateRange::MAX_DAYS = 480` — Google's practical upper bound. Search Console has ~16 months of data. Ranges over `MAX_DAYS` truncate on Google's side.

## Static constructors

### `DateRange::lastDays( int $days ): self`

The most common shape — a rolling window ending on today's date. Google typically finalises data 2–3 days behind, so recent days may still show low or zero values.

```php
DateRange::lastDays( 28 );  // last 28 days
DateRange::lastDays( 7 );   // last week
DateRange::lastDays( 480 ); // max
```

### `DateRange::between( DateTimeInterface|string $start, DateTimeInterface|string $end ): self`

An explicit window. Accepts either PHP `DateTime` / `Carbon` instances or ISO strings.

```php
DateRange::between( '2026-01-01', '2026-01-31' );
DateRange::between( Carbon::parse( '2026-01-01' ), Carbon::parse( '2026-01-31' ) );
```

## Usage

Fetchers take a `DateRange` directly:

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;

$fetcher->fetch( $connection, DateRange::lastDays( 28 ) );
```

`SearchAnalyticsRequest` too:

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsRequest;

new SearchAnalyticsRequest(
    dateRange:  DateRange::between( '2026-01-01', '2026-01-31' ),
    dimensions: [ 'query' ],
);
```

## Serialization

`DateRange` is serialized to the API payload via `SearchAnalyticsRequest::toApiPayload()`:

```php
[
    'startDate' => $this->dateRange->startDate,  // 'YYYY-MM-DD'
    'endDate'   => $this->dateRange->endDate,    // 'YYYY-MM-DD'
    // dimensions, filters, rowLimit, startRow, type, dataState conditionally appended
]
```

The strings ship verbatim — no reformatting or timezone conversion. Both dates are interpreted by Google in UTC.

## Common pitfalls

- **Off-by-one on the trailing day.** Search Console's most recent 2–3 days are usually incomplete. `DateRange::lastDays(7)` includes today, which may show as zero. If you want "the last week of *final* data", subtract 3: `DateRange::between( today()->subDays(9), today()->subDays(3) )`.
- **Ranges over 16 months.** Google returns rows only for dates where data exists — a `DateRange` spanning years beyond the Search Console retention window quietly returns zero rows for the missing days.
- **Timezone.** Both dates are treated as UTC calendar days by Google. If your app's timezone is Pacific and you build a range from `now()->toDateString()`, you may include or exclude a day the user thinks is "today".

## Testing

Deterministic; no side effects. Use it directly in tests:

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;

it( 'lastDays returns an inclusive range', function () {
    $range = DateRange::lastDays( 7 );

    expect( $range->startDate )->toBeString();
    expect( $range->endDate )->toBeString();
    expect( $range->endDate >= $range->startDate )->toBeTrue();
} );
```
