<?php

declare( strict_types=1 );

use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsRequest;

it( 'serialises the basic searchAnalytics.query payload shape', function (): void {
    $request = new SearchAnalyticsRequest(
        dateRange: new DateRange( '2026-01-01', '2026-01-07' ),
    );

    expect( $request->toApiPayload() )->toBe( [
        'startDate' => '2026-01-01',
        'endDate'   => '2026-01-07',
    ] );
} );

it( 'includes dimensions when provided', function (): void {
    $request = new SearchAnalyticsRequest(
        dateRange: new DateRange( '2026-01-01', '2026-01-07' ),
        dimensions: [ 'query', 'page' ],
    );

    $payload = $request->toApiPayload();

    expect( $payload )->toHaveKey( 'dimensions' );
    expect( $payload['dimensions'] )->toBe( [ 'query', 'page' ] );
} );

it( 'clamps rowLimit to the API maximum', function (): void {
    $request = new SearchAnalyticsRequest(
        dateRange: new DateRange( '2026-01-01', '2026-01-07' ),
        rowLimit: 999_999,
    );

    expect( $request->toApiPayload()['rowLimit'] )->toBe( SearchAnalyticsRequest::MAX_ROW_LIMIT );
} );

it( 'passes through startRow, searchType, and dataState when set', function (): void {
    $request = new SearchAnalyticsRequest(
        dateRange: new DateRange( '2026-01-01', '2026-01-07' ),
        rowLimit: 100,
        startRow: 200,
        searchType: 'web',
        dataState: 'all',
    );

    $payload = $request->toApiPayload();

    expect( $payload['rowLimit'] )->toBe( 100 );
    expect( $payload['startRow'] )->toBe( 200 );
    expect( $payload['type'] )->toBe( 'web' );
    expect( $payload['dataState'] )->toBe( 'all' );
} );

it( 'never emits orderBys — the Search Console API does not accept a sort field', function (): void {
    $request = new SearchAnalyticsRequest(
        dateRange: new DateRange( '2026-01-01', '2026-01-07' ),
        dimensions: [ 'query' ],
    );

    $payload = $request->toApiPayload();

    expect( $payload )->not->toHaveKey( 'orderBys' );
    expect( $payload )->not->toHaveKey( 'orderBy' );
} );

it( 'omits paging keys when not set', function (): void {
    $request = new SearchAnalyticsRequest(
        dateRange: new DateRange( '2026-01-01', '2026-01-07' ),
    );

    $payload = $request->toApiPayload();

    expect( $payload )->not->toHaveKey( 'rowLimit' );
    expect( $payload )->not->toHaveKey( 'startRow' );
    expect( $payload )->not->toHaveKey( 'type' );
    expect( $payload )->not->toHaveKey( 'dataState' );
    expect( $payload )->not->toHaveKey( 'dimensions' );
} );
