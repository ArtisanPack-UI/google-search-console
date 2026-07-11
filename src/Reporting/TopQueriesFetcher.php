<?php

/**
 * Fetch logic for the Search Console top queries surface.
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
 * Pulls the top search queries (by clicks) from the Search Console API
 * for a configurable date range.
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
class TopQueriesFetcher
{
    /**
     * Default upper bound on rows returned. The UI paginates locally,
     * so returning a few hundred rows in one call is cheap and keeps
     * sort/paginate behavior snappy.
     */
    public const DEFAULT_LIMIT = 25;

    public function __construct(
        protected SearchAnalyticsClient $client,
    ) {
    }

    /**
     * Fetch top queries for the given range.
     *
     * @since 1.0.0
     */
    public function fetch(
        GoogleConnection $connection,
        DateRange $range,
        ?string $siteUrl = null,
        int $limit = self::DEFAULT_LIMIT,
    ): TopQueriesData {
        $limit = $this->clampLimit( $limit );

        $response = $this->client->query(
            new SearchAnalyticsRequest(
                dateRange: $range,
                dimensions: [ 'query' ],
                rowLimit: $limit,
            ),
            $connection,
            $siteUrl,
        );

        return new TopQueriesData(
            rows: $this->parseRows( $response ),
            dateRange: $range,
        );
    }

    /**
     * @return list<array{query: string, clicks: float, impressions: float, ctr: float, position: float}>
     */
    protected function parseRows( SearchAnalyticsResponse $response ): array
    {
        $out = [];

        foreach ( $response->rows() as $row ) {
            $out[] = [
                'query'       => (string) ( $row['query'] ?? '' ),
                'clicks'      => (float) ( $row['clicks'] ?? 0 ),
                'impressions' => (float) ( $row['impressions'] ?? 0 ),
                'ctr'         => (float) ( $row['ctr'] ?? 0 ),
                'position'    => (float) ( $row['position'] ?? 0 ),
            ];
        }

        usort( $out, static fn ( array $a, array $b ): int => $b['clicks'] <=> $a['clicks'] );

        return $out;
    }

    /**
     * Clamp a caller-supplied limit into a sane range.
     */
    protected function clampLimit( int $limit ): int
    {
        return max( 1, min( $limit, 1000 ) );
    }
}
