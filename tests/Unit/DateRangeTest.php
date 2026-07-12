<?php

declare( strict_types=1 );

use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;

it( 'accepts YYYY-MM-DD start and end dates', function (): void {
    $range = new DateRange( '2026-01-01', '2026-01-31' );

    expect( $range->toArray() )->toBe( [
        'startDate' => '2026-01-01',
        'endDate'   => '2026-01-31',
    ] );
} );

it( 'rejects empty date values', function (): void {
    new DateRange( '', '2026-01-01' );
} )->throws( InvalidArgumentException::class );

it( 'rejects malformed date values', function (): void {
    new DateRange( 'not-a-date', '2026-01-01' );
} )->throws( InvalidArgumentException::class );

it( 'rejects impossible calendar dates', function (): void {
    new DateRange( '2026-02-30', '2026-01-01' );
} )->throws( InvalidArgumentException::class );

it( 'builds a range covering the last N days ending today', function (): void {
    $range = DateRange::lastDays( 7 );

    $end   = Illuminate\Support\Carbon::now()->startOfDay();
    $start = ( clone $end )->subDays( 6 );

    expect( $range->endDate )->toBe( $end->format( 'Y-m-d' ) );
    expect( $range->startDate )->toBe( $start->format( 'Y-m-d' ) );
} );

it( 'rejects a non-positive lastDays value', function (): void {
    DateRange::lastDays( 0 );
} )->throws( InvalidArgumentException::class );

it( 'clamps absurd lastDays values to MAX_DAYS', function (): void {
    $range = DateRange::lastDays( 100_000 );

    $end   = Illuminate\Support\Carbon::now()->startOfDay();
    $start = ( clone $end )->subDays( DateRange::MAX_DAYS - 1 );

    expect( $range->startDate )->toBe( $start->format( 'Y-m-d' ) );
} );

it( 'builds a range from DateTime instances', function (): void {
    $range = DateRange::between(
        new DateTimeImmutable( '2026-03-15' ),
        new DateTimeImmutable( '2026-04-15' ),
    );

    expect( $range->startDate )->toBe( '2026-03-15' );
    expect( $range->endDate )->toBe( '2026-04-15' );
} );
