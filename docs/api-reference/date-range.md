---
title: DateRange
---

# `DateRange`

Immutable inclusive date window. FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange`.

Full usage guide: [[Reporting/Date Range]]. This page is the class-level reference only.

## Constants

- `DateRange::MAX_DAYS = 480` — practical upper bound (roughly 16 months, matching Google's retention window).

## Constructor

```php
public function __construct(
    public readonly string $startDate,
    public readonly string $endDate,
)
```

Both dates ISO 8601 (`YYYY-MM-DD`). Inclusive on both ends.

## Static constructors

### `DateRange::lastDays( int $days ): self`

A rolling window ending on today's date. `$days` is truncated to `1..MAX_DAYS` if outside the range.

### `DateRange::between( DateTimeInterface|string $start, DateTimeInterface|string $end ): self`

Explicit window. Accepts PHP `DateTime` / `Carbon` instances or ISO strings; both are normalised to `YYYY-MM-DD`.

## Serialisation

Consumed by `SearchAnalyticsRequest::toApiPayload()`:

```php
[
    'startDate' => $range->startDate,  // 'YYYY-MM-DD'
    'endDate'   => $range->endDate,    // 'YYYY-MM-DD'
]
```

No timezone conversion — Google interprets both as UTC calendar days.

## See also

- [[Reporting/Date Range]] — full usage + pitfalls.
- [[API Reference/Search Analytics Request]] — accepts a `DateRange`.
- [[API Reference/Fetchers]] — every fetcher takes a `DateRange`.
