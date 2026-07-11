<?php

/**
 * Fetch logic for the Search Console top pages surface.
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
 * Pulls the top landing pages (by clicks) from the Search Console API
 * for a configurable date range.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class TopPagesFetcher
{
    public const DEFAULT_LIMIT = 25;

    public function __construct(
        protected SearchAnalyticsClient $client,
    ) {
    }

    /**
     * Fetch top pages for the given range.
     *
     * @since 1.0.0
     */
    public function fetch(
        GoogleConnection $connection,
        DateRange $range,
        ?string $siteUrl = null,
        int $limit = self::DEFAULT_LIMIT,
    ): TopPagesData {
        $limit = $this->clampLimit( $limit );

        $response = $this->client->query(
            new SearchAnalyticsRequest(
                dateRange: $range,
                dimensions: [ 'page' ],
                rowLimit: $limit,
            ),
            $connection,
            $siteUrl,
        );

        return new TopPagesData(
            rows: $this->parseRows( $response ),
            dateRange: $range,
        );
    }

    /**
     * @return list<array{page: string, clicks: float, impressions: float, ctr: float, position: float}>
     */
    protected function parseRows( SearchAnalyticsResponse $response ): array
    {
        $out = [];

        foreach ( $response->rows() as $row ) {
            $out[] = [
                'page'        => (string) ( $row['page'] ?? '' ),
                'clicks'      => (float) ( $row['clicks'] ?? 0 ),
                'impressions' => (float) ( $row['impressions'] ?? 0 ),
                'ctr'         => (float) ( $row['ctr'] ?? 0 ),
                'position'    => (float) ( $row['position'] ?? 0 ),
            ];
        }

        usort( $out, static fn ( array $a, array $b ): int => $b['clicks'] <=> $a['clicks'] );

        return $out;
    }

    protected function clampLimit( int $limit ): int
    {
        return max( 1, min( $limit, 1000 ) );
    }
}
