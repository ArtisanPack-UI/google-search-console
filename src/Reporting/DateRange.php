<?php

/**
 * Immutable start/end date range for a Search Console API request.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Reporting;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Simple value object wrapping a start and end date in the format the
 * Search Console API expects (YYYY-MM-DD).
 *
 * Unlike GA4, Search Console does not accept relative aliases like
 * "today" or "7daysAgo" — the API only takes concrete calendar dates,
 * so `lastDays()` computes concrete dates at construction time.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
final class DateRange
{
    /**
     * Upper bound on the days-back window this class will build. Search
     * Console retains 16 months of performance data; anything wider
     * would return empty rows and is almost certainly a mis-plumbed
     * input rather than a real query.
     */
    public const MAX_DAYS = 490;

    /**
     * @param  string  $startDate  Start date (YYYY-MM-DD).
     * @param  string  $endDate  End date (YYYY-MM-DD).
     */
    public function __construct(
        public readonly string $startDate,
        public readonly string $endDate,
    ) {
        $this->guard( $startDate, 'startDate' );
        $this->guard( $endDate, 'endDate' );
    }

    /**
     * Build a range covering the last N days ending today.
     *
     * @since 1.0.0
     */
    public static function lastDays( int $days ): self
    {
        if ( $days < 1 ) {
            throw new InvalidArgumentException( 'DateRange::lastDays expects a positive day count.' );
        }

        if ( $days > self::MAX_DAYS ) {
            $days = self::MAX_DAYS;
        }

        $end   = Carbon::now()->startOfDay();
        $start = ( clone $end )->subDays( $days - 1 );

        return new self( $start->format( 'Y-m-d' ), $end->format( 'Y-m-d' ) );
    }

    /**
     * Build a range from two DateTime-like values.
     *
     * @since 1.0.0
     */
    public static function between( DateTimeInterface|string $start, DateTimeInterface|string $end ): self
    {
        return new self(
            $start instanceof DateTimeInterface ? $start->format( 'Y-m-d' ) : (string) $start,
            $end instanceof DateTimeInterface ? $end->format( 'Y-m-d' ) : (string) $end,
        );
    }

    /**
     * Serialize the range to the shape the Search Console API expects
     * inside a searchAnalytics.query request body.
     *
     * @since 1.0.0
     *
     * @return array{startDate: string, endDate: string}
     */
    public function toArray(): array
    {
        return [
            'startDate' => $this->startDate,
            'endDate'   => $this->endDate,
        ];
    }

    /**
     * Guard that the raw string is a valid YYYY-MM-DD calendar date. Runs
     * at construction so callers get an error at the point they build the
     * range, not deep inside an API response parser.
     *
     * Carbon::createFromFormat returns null (not throws) when the input
     * doesn't match the strict format — no try/catch needed — and the
     * format-round-trip check rejects both the empty string and
     * impossible dates like 2026-02-30 (which parse to a valid Carbon
     * but format differently).
     */
    private function guard( string $value, string $label ): void
    {
        $parsed = Carbon::createFromFormat( '!Y-m-d', $value );

        if ( null === $parsed || $parsed->format( 'Y-m-d' ) !== $value ) {
            throw new InvalidArgumentException(
                sprintf( 'DateRange::%s value "%s" is not a valid YYYY-MM-DD date.', $label, $value ),
            );
        }
    }
}
