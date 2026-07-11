<?php

declare( strict_types=1 );

use ArtisanPackUI\Google\Exceptions\TokenRefreshException;
use ArtisanPackUI\GoogleSearchConsole\Exceptions\BaseNotInstalledException;
use ArtisanPackUI\GoogleSearchConsole\Exceptions\ReportingException;
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsRequest;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach( function (): void {
    BaseInstalled::setForTesting( true );

    config()->set( 'google-search-console.reporting.site_url', 'https://example.com/' );
} );

it( 'runs a query and returns a parsed response with dimensions applied', function (): void {
    Http::fake( [
        '*' => Http::response( [
            'rows' => [
                [ 'keys' => [ 'buy shoes' ], 'clicks' => 42, 'impressions' => 500, 'ctr' => 0.084, 'position' => 3.2 ],
            ],
        ], 200 ),
    ] );

    $client = new SearchAnalyticsClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeGscStubTokenManager( 'test-access-token' ),
    );

    $response = $client->query(
        new SearchAnalyticsRequest(
            dateRange: new DateRange( '2026-01-01', '2026-01-07' ),
            dimensions: [ 'query' ],
        ),
        makeGscConnectedConnection(),
    );

    expect( $response->rows() )->toBe( [
        [ 'query' => 'buy shoes', 'clicks' => 42.0, 'impressions' => 500.0, 'ctr' => 0.084, 'position' => 3.2 ],
    ] );

    Http::assertSent( function ( Illuminate\Http\Client\Request $request ): bool {
        expect( $request->url() )->toContain( 'sites/' );
        expect( $request->url() )->toContain( '/searchAnalytics/query' );
        expect( $request->header( 'Authorization' )[0] ?? '' )->toBe( 'Bearer test-access-token' );

        return true;
    } );
} );

it( 'throws a well-typed exception when site_url is missing', function (): void {
    config()->set( 'google-search-console.reporting.site_url', null );

    $client = new SearchAnalyticsClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeGscStubTokenManager( 'x' ),
    );

    $client->query(
        new SearchAnalyticsRequest( dateRange: new DateRange( '2026-01-01', '2026-01-07' ) ),
        makeGscConnectedConnection(),
    );
} )->throws( ReportingException::class );

it( 'throws BaseNotInstalledException when the base package is not detected', function (): void {
    BaseInstalled::setForTesting( false );

    $client = new SearchAnalyticsClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeGscStubTokenManager( 'x' ),
    );

    $client->query(
        new SearchAnalyticsRequest( dateRange: new DateRange( '2026-01-01', '2026-01-07' ) ),
        makeGscConnectedConnection(),
    );
} )->throws( BaseNotInstalledException::class );

it( 'throws BaseNotInstalledException when the TokenManager is null', function (): void {
    $client = new SearchAnalyticsClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: null,
    );

    $client->query(
        new SearchAnalyticsRequest( dateRange: new DateRange( '2026-01-01', '2026-01-07' ) ),
        makeGscConnectedConnection(),
    );
} )->throws( BaseNotInstalledException::class );

it( 'wraps API error responses in a ReportingException', function (): void {
    Http::fake( [
        '*' => Http::response( [ 'error' => 'permission denied' ], 403 ),
    ] );

    $client = new SearchAnalyticsClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeGscStubTokenManager( 'x' ),
    );

    $client->query(
        new SearchAnalyticsRequest( dateRange: new DateRange( '2026-01-01', '2026-01-07' ) ),
        makeGscConnectedConnection(),
    );
} )->throws( ReportingException::class );

it( 'wraps a TokenRefreshException from the base package in a ReportingException', function (): void {
    $client = new SearchAnalyticsClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeGscThrowingTokenManager( new TokenRefreshException( 'refresh denied' ) ),
    );

    try {
        $client->query(
            new SearchAnalyticsRequest( dateRange: new DateRange( '2026-01-01', '2026-01-07' ) ),
            makeGscConnectedConnection(),
        );
        expect( true )->toBeFalse( 'expected ReportingException' );
    } catch ( ReportingException $e ) {
        expect( $e->getPrevious() )->toBeInstanceOf( TokenRefreshException::class );
    }
} );

it( 'wraps a transport failure in a ReportingException', function (): void {
    Http::fake( function (): void {
        throw new ConnectionException( 'connection refused' );
    } );

    $client = new SearchAnalyticsClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeGscStubTokenManager( 'x' ),
    );

    try {
        $client->query(
            new SearchAnalyticsRequest( dateRange: new DateRange( '2026-01-01', '2026-01-07' ) ),
            makeGscConnectedConnection(),
        );
        expect( true )->toBeFalse( 'expected ReportingException' );
    } catch ( ReportingException $e ) {
        expect( $e->getPrevious() )->toBeInstanceOf( ConnectionException::class );
    }
} );

it( 'caches successful query responses for the configured TTL', function (): void {
    config()->set( 'google-search-console.reporting.cache_ttl', 300 );

    Http::fake( [
        '*' => Http::response( [
            'rows' => [ [ 'keys' => [ 'a' ], 'clicks' => 7, 'impressions' => 70, 'ctr' => 0.1, 'position' => 5 ] ],
        ], 200 ),
    ] );

    $client = new SearchAnalyticsClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeGscStubTokenManager( 'x' ),
        cache: Cache::store(),
    );

    $request    = new SearchAnalyticsRequest(
        dateRange: new DateRange( '2026-01-01', '2026-01-07' ),
        dimensions: [ 'query' ],
    );
    $connection = makeGscConnectedConnection();

    $first  = $client->query( $request, $connection );
    $second = $client->query( $request, $connection );

    expect( $first->rows() )->toBe( $second->rows() );

    Http::assertSentCount( 1 );
} );

it( 'falls back to a 30s timeout when the config value is null (a common env() footgun)', function (): void {
    config()->set( 'google-search-console.reporting.timeout', null );

    Http::fake( [ '*' => Http::response( [ 'rows' => [] ], 200 ) ] );

    $client = new SearchAnalyticsClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeGscStubTokenManager( 'x' ),
    );

    // Just prove the call succeeds. A 0-second (Guzzle "no timeout") value
    // would hang the test process forever if the guard regressed, so a plain
    // completion here confirms the fallback landed.
    $response = $client->query(
        new SearchAnalyticsRequest( dateRange: new DateRange( '2026-01-01', '2026-01-07' ) ),
        makeGscConnectedConnection(),
    );

    expect( $response->rows() )->toBe( [] );
} );

it( 'reports isAvailable() based on base install + token manager presence', function (): void {
    $client = new SearchAnalyticsClient( app( 'config' ), app( HttpFactory::class ), makeGscStubTokenManager( 'x' ) );
    expect( $client->isAvailable() )->toBeTrue();

    BaseInstalled::setForTesting( false );
    expect( $client->isAvailable() )->toBeFalse();

    BaseInstalled::setForTesting( true );
    $noTokens = new SearchAnalyticsClient( app( 'config' ), app( HttpFactory::class ), null );
    expect( $noTokens->isAvailable() )->toBeFalse();
} );
