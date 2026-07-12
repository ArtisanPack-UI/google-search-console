<?php

/**
 * Shared fetch logic for the Search Console performance overview.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Reporting;

use ArtisanPackUI\Google\Models\GoogleConnection;

/**
 * Pulls the four headline metrics (clicks, impressions, avg CTR, avg
 * position) and a daily trend from the Search Console API for a
 * configurable date range.
 *
 * Shared by the Livewire component and the HTTP endpoint that powers
 * the React and Vue components so all three surfaces see identical
 * numbers for the same input.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class PerformanceOverviewFetcher
{
    public function __construct(
        protected SearchAnalyticsClient $client,
    ) {
    }

    /**
     * Fetch overview totals and daily trend for the given range.
     *
     * @since 1.0.0
     */
    public function fetch( GoogleConnection $connection, DateRange $range, ?string $siteUrl = null ): PerformanceOverviewData
    {
        $totals = $this->client->query(
            new SearchAnalyticsRequest( dateRange: $range ),
            $connection,
            $siteUrl,
        );

        $trend = $this->client->query(
            new SearchAnalyticsRequest(
                dateRange: $range,
                dimensions: [ 'date' ],
                rowLimit: SearchAnalyticsRequest::MAX_ROW_LIMIT,
            ),
            $connection,
            $siteUrl,
        );

        return new PerformanceOverviewData(
            totals: $this->parseTotals( $totals ),
            trend: $this->parseTrend( $trend ),
            dateRange: $range,
            hasData: [] !== $totals->rows(),
        );
    }

    /**
     * @return array{clicks: float, impressions: float, ctr: float, position: float}
     */
    protected function parseTotals( SearchAnalyticsResponse $response ): array
    {
        $rows  = $response->rows();
        $first = $rows[0] ?? [];

        return [
            'clicks'      => (float) ( $first['clicks'] ?? 0 ),
            'impressions' => (float) ( $first['impressions'] ?? 0 ),
            'ctr'         => (float) ( $first['ctr'] ?? 0 ),
            'position'    => (float) ( $first['position'] ?? 0 ),
        ];
    }

    /**
     * @return list<array{date: string, clicks: float, impressions: float, ctr: float, position: float}>
     */
    protected function parseTrend( SearchAnalyticsResponse $response ): array
    {
        $out = [];

        foreach ( $response->rows() as $row ) {
            $out[] = [
                'date'        => (string) ( $row['date'] ?? '' ),
                'clicks'      => (float) ( $row['clicks'] ?? 0 ),
                'impressions' => (float) ( $row['impressions'] ?? 0 ),
                'ctr'         => (float) ( $row['ctr'] ?? 0 ),
                'position'    => (float) ( $row['position'] ?? 0 ),
            ];
        }

        usort( $out, static fn ( array $a, array $b ): int => strcmp( (string) $a['date'], (string) $b['date'] ) );

        return $out;
    }
}
