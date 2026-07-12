<?php

/**
 * HTTP controller powering the React and Vue top queries table.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Http\Controllers;

use ArtisanPackUI\GoogleSearchConsole\Exceptions\BaseNotInstalledException;
use ArtisanPackUI\GoogleSearchConsole\Exceptions\ReportingException;
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Reporting\TopQueriesFetcher;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns the top queries payload the React and Vue components render.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class TopQueriesController
{
    public function __invoke( Request $request ): JsonResponse
    {
        if ( ! BaseInstalled::check() ) {
            return response()->json( [
                'error'         => 'base_not_installed',
                'message'       => __( 'The Search Console top queries view requires the artisanpack-ui/google base package.' ),
                'baseInstalled' => false,
            ], 501 );
        }

        $days  = $this->clampDays( $request->query( 'days', 28 ) );
        $limit = $this->clampLimit( $request->query( 'limit', TopQueriesFetcher::DEFAULT_LIMIT ) );

        // See PerformanceOverviewController — site_url is deliberately
        // config-only to prevent cross-property leakage in multi-tenant
        // setups where one Google account owns several verified sites.
        $siteUrl = null;

        $user = $request->user();

        if ( null === $user ) {
            return response()->json( [
                'error'   => 'unauthenticated',
                'message' => __( 'Sign in to view Search Console data.' ),
            ], 401 );
        }

        $connection = app( GoogleConnectionResolver::class )->forUser( $user );

        if ( null === $connection ) {
            return response()->json( [
                'error'   => 'not_connected',
                'message' => __( 'Connect a Google account to view Search Console data.' ),
            ], 409 );
        }

        try {
            /** @var SearchAnalyticsClient $client */
            $client  = app( SearchAnalyticsClient::class );
            $fetcher = new TopQueriesFetcher( $client );
            $data    = $fetcher->fetch( $connection, DateRange::lastDays( $days ), $siteUrl, $limit );

            return response()->json( $data->toArray() );
        } catch ( BaseNotInstalledException $e ) {
            return response()->json( [
                'error'   => 'base_not_installed',
                'message' => $e->getMessage(),
            ], 501 );
        } catch ( ReportingException $e ) {
            return response()->json( [
                'error'   => 'reporting_error',
                'message' => $e->getMessage(),
            ], 502 );
        }
    }

    protected function clampDays( mixed $raw ): int
    {
        if ( ! is_scalar( $raw ) ) {
            return 28;
        }

        $days = (int) $raw;

        if ( $days < 1 ) {
            return 1;
        }

        return min( $days, DateRange::MAX_DAYS );
    }

    protected function clampLimit( mixed $raw ): int
    {
        if ( ! is_scalar( $raw ) ) {
            return TopQueriesFetcher::DEFAULT_LIMIT;
        }

        $limit = (int) $raw;

        if ( $limit < 1 ) {
            return 1;
        }

        return min( $limit, 1000 );
    }
}
