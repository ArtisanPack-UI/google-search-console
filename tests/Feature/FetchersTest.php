<?php

declare( strict_types=1 );

use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Reporting\TopPagesFetcher;
use ArtisanPackUI\GoogleSearchConsole\Reporting\TopQueriesFetcher;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

beforeEach( function (): void {
    BaseInstalled::setForTesting( true );
    config()->set( 'google-search-console.reporting.site_url', 'https://example.com/' );
} );

it( 'PerformanceOverviewFetcher parses totals and daily trend from two API calls', function (): void {
    $totalsBody = [
        'rows' => [
            [ 'keys' => [], 'clicks' => 100, 'impressions' => 1000, 'ctr' => 0.1, 'position' => 8.5 ],
        ],
    ];

    $trendBody = [
        'rows' => [
            [ 'keys' => [ '2026-01-02' ], 'clicks' => 60, 'impressions' => 600, 'ctr' => 0.1, 'position' => 8.4 ],
            [ 'keys' => [ '2026-01-01' ], 'clicks' => 40, 'impressions' => 400, 'ctr' => 0.1, 'position' => 8.6 ],
        ],
    ];

    Http::fakeSequence()
        ->push( $totalsBody, 200 )
        ->push( $trendBody, 200 );

    $client  = new SearchAnalyticsClient( app( 'config' ), app( HttpFactory::class ), makeGscStubTokenManager( 'x' ) );
    $fetcher = new PerformanceOverviewFetcher( $client );

    $data = $fetcher->fetch( makeGscConnectedConnection(), DateRange::lastDays( 7 ) );

    expect( $data->totals )->toBe( [
        'clicks'      => 100.0,
        'impressions' => 1000.0,
        'ctr'         => 0.1,
        'position'    => 8.5,
    ] );

    expect( $data->trend )->toBe( [
        [ 'date' => '2026-01-01', 'clicks' => 40.0, 'impressions' => 400.0, 'ctr' => 0.1, 'position' => 8.6 ],
        [ 'date' => '2026-01-02', 'clicks' => 60.0, 'impressions' => 600.0, 'ctr' => 0.1, 'position' => 8.4 ],
    ] );
} );

it( 'TopQueriesFetcher returns queries sorted by clicks desc', function (): void {
    Http::fake( [
        '*' => Http::response( [
            'rows' => [
                [ 'keys' => [ 'low' ],  'clicks' => 5,  'impressions' => 50, 'ctr' => 0.1, 'position' => 6 ],
                [ 'keys' => [ 'high' ], 'clicks' => 50, 'impressions' => 200, 'ctr' => 0.25, 'position' => 2 ],
                [ 'keys' => [ 'mid' ],  'clicks' => 20, 'impressions' => 100, 'ctr' => 0.2, 'position' => 4 ],
            ],
        ], 200 ),
    ] );

    $client  = new SearchAnalyticsClient( app( 'config' ), app( HttpFactory::class ), makeGscStubTokenManager( 'x' ) );
    $fetcher = new TopQueriesFetcher( $client );

    $data = $fetcher->fetch( makeGscConnectedConnection(), DateRange::lastDays( 7 ) );

    expect( array_column( $data->rows, 'query' ) )->toBe( [ 'high', 'mid', 'low' ] );
} );

it( 'PerformanceOverviewFetcher flags an empty API response with hasData=false', function (): void {
    // Search Console can return `{}` for a range with no data (fresh property
    // or the trailing 2-3 days that GSC withholds for finalisation). The
    // fetcher must not fold that into all-zero totals.
    Http::fakeSequence()
        ->push( [], 200 )
        ->push( [], 200 );

    $client  = new SearchAnalyticsClient( app( 'config' ), app( HttpFactory::class ), makeGscStubTokenManager( 'x' ) );
    $fetcher = new PerformanceOverviewFetcher( $client );

    $data = $fetcher->fetch( makeGscConnectedConnection(), DateRange::lastDays( 7 ) );

    expect( $data->hasData )->toBeFalse();
    expect( $data->toArray()[ 'has_data' ] )->toBeFalse();
} );

it( 'PerformanceOverviewFetcher flags a real response with hasData=true', function (): void {
    Http::fakeSequence()
        ->push( [ 'rows' => [ [ 'keys' => [], 'clicks' => 5, 'impressions' => 50, 'ctr' => 0.1, 'position' => 8 ] ] ], 200 )
        ->push( [ 'rows' => [] ], 200 );

    $client  = new SearchAnalyticsClient( app( 'config' ), app( HttpFactory::class ), makeGscStubTokenManager( 'x' ) );
    $fetcher = new PerformanceOverviewFetcher( $client );

    $data = $fetcher->fetch( makeGscConnectedConnection(), DateRange::lastDays( 7 ) );

    expect( $data->hasData )->toBeTrue();
} );

it( 'TopPagesFetcher returns pages sorted by clicks desc', function (): void {
    Http::fake( [
        '*' => Http::response( [
            'rows' => [
                [ 'keys' => [ '/a' ], 'clicks' => 3,  'impressions' => 30, 'ctr' => 0.1, 'position' => 8 ],
                [ 'keys' => [ '/b' ], 'clicks' => 33, 'impressions' => 300, 'ctr' => 0.11, 'position' => 4 ],
                [ 'keys' => [ '/c' ], 'clicks' => 15, 'impressions' => 150, 'ctr' => 0.1, 'position' => 6 ],
            ],
        ], 200 ),
    ] );

    $client  = new SearchAnalyticsClient( app( 'config' ), app( HttpFactory::class ), makeGscStubTokenManager( 'x' ) );
    $fetcher = new TopPagesFetcher( $client );

    $data = $fetcher->fetch( makeGscConnectedConnection(), DateRange::lastDays( 7 ) );

    expect( array_column( $data->rows, 'page' ) )->toBe( [ '/b', '/c', '/a' ] );
} );
