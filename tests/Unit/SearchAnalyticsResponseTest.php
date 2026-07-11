<?php

declare( strict_types=1 );

use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsResponse;

it( 'flattens rows into associative arrays keyed by the requested dimensions', function (): void {
    $raw = [
        'rows' => [
            [
                'keys'        => [ 'buy shoes', 'https://example.com/shoes' ],
                'clicks'      => 42,
                'impressions' => 500,
                'ctr'         => 0.084,
                'position'    => 3.2,
            ],
            [
                'keys'        => [ 'best shoes', 'https://example.com/best' ],
                'clicks'      => 10,
                'impressions' => 200,
                'ctr'         => 0.05,
                'position'    => 5.1,
            ],
        ],
    ];

    $response = new SearchAnalyticsResponse( $raw, [ 'query', 'page' ] );

    expect( $response->rows() )->toBe( [
        [
            'query'       => 'buy shoes',
            'page'        => 'https://example.com/shoes',
            'clicks'      => 42.0,
            'impressions' => 500.0,
            'ctr'         => 0.084,
            'position'    => 3.2,
        ],
        [
            'query'       => 'best shoes',
            'page'        => 'https://example.com/best',
            'clicks'      => 10.0,
            'impressions' => 200.0,
            'ctr'         => 0.05,
            'position'    => 5.1,
        ],
    ] );
} );

it( 'returns an empty array when there are no rows', function (): void {
    expect( ( new SearchAnalyticsResponse( [] ) )->rows() )->toBe( [] );
} );

it( 'sums a metric across all rows', function (): void {
    $raw = [
        'rows' => [
            [ 'keys' => [ 'a' ], 'clicks' => 10, 'impressions' => 100, 'ctr' => 0.1, 'position' => 3 ],
            [ 'keys' => [ 'b' ], 'clicks' => 20, 'impressions' => 200, 'ctr' => 0.1, 'position' => 4 ],
        ],
    ];

    $response = new SearchAnalyticsResponse( $raw, [ 'query' ] );

    expect( $response->totalFor( 'clicks' ) )->toBe( 30.0 );
    expect( $response->totalFor( 'impressions' ) )->toBe( 300.0 );
} );

it( 'defaults missing metric values to zero', function (): void {
    $raw = [
        'rows' => [
            [ 'keys' => [ 'a' ] ],
        ],
    ];

    $response = new SearchAnalyticsResponse( $raw, [ 'query' ] );

    expect( $response->rows()[0] )->toBe( [
        'query'       => 'a',
        'clicks'      => 0.0,
        'impressions' => 0.0,
        'ctr'         => 0.0,
        'position'    => 0.0,
    ] );
} );
