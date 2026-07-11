<?php

/**
 * Parsed Search Console searchAnalytics.query response.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Reporting;

/**
 * Read-only wrapper over the raw JSON body returned by the Search
 * Console API's searchAnalytics.query endpoint. Exposes convenience
 * accessors so callers do not need to walk deep nested arrays.
 *
 * The API returns each row as `{ keys: [d1, d2, ...], clicks,
 * impressions, ctr, position }`. This class flattens each row into
 * `[dimensionName => value, clicks, impressions, ctr, position]` so
 * consumers can render tables and totals directly.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
final class SearchAnalyticsResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $dimensions  The dimension names, in the order
     *                                    they appear in each row's `keys`
     *                                    array. Search Console does not
     *                                    echo dimension headers, so callers
     *                                    must pass what they requested.
     */
    public function __construct(
        public readonly array $raw,
        public readonly array $dimensions = [],
    ) {
    }

    /**
     * The response type ("web", "image", "video", "news", "discover", "googleNews").
     *
     * @since 1.0.0
     */
    public function responseAggregationType(): string
    {
        return (string) ( $this->raw['responseAggregationType'] ?? '' );
    }

    /**
     * Iterate the response rows as associative arrays with dimension
     * names as keys plus the four metric fields.
     *
     * @since 1.0.0
     *
     * @return list<array<string, float|string>>
     */
    public function rows(): array
    {
        $rows = $this->raw['rows'] ?? [];

        if ( ! is_array( $rows ) ) {
            return [];
        }

        $result = [];

        foreach ( $rows as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }

            $mapped = [];

            $keys = is_array( $row['keys'] ?? null ) ? $row['keys'] : [];

            foreach ( $this->dimensions as $index => $dimension ) {
                $mapped[ $dimension ] = (string) ( $keys[ $index ] ?? '' );
            }

            $mapped['clicks']      = (float) ( $row['clicks'] ?? 0 );
            $mapped['impressions'] = (float) ( $row['impressions'] ?? 0 );
            $mapped['ctr']         = (float) ( $row['ctr'] ?? 0 );
            $mapped['position']    = (float) ( $row['position'] ?? 0 );

            $result[] = $mapped;
        }

        return $result;
    }

    /**
     * Sum the values of a single metric across every row.
     *
     * Useful for pulling top-level totals like "clicks" out of a
     * dimensioned report without walking the raw shape.
     *
     * @since 1.0.0
     */
    public function totalFor( string $metric ): float
    {
        $total = 0.0;

        foreach ( $this->rows() as $row ) {
            if ( isset( $row[ $metric ] ) && is_numeric( $row[ $metric ] ) ) {
                $total += (float) $row[ $metric ];
            }
        }

        return $total;
    }
}
