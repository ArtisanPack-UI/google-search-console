<?php

declare( strict_types=1 );

use ArtisanPackUI\Google\Scopes\ScopeRegistry;
use ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsole;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;

it( 'registers the SearchAnalyticsClient as a singleton', function (): void {
    $first  = app( SearchAnalyticsClient::class );
    $second = app( SearchAnalyticsClient::class );

    expect( $first )->toBe( $second );
} );

it( 'registers the GoogleConnectionResolver as a singleton', function (): void {
    $first  = app( GoogleConnectionResolver::class );
    $second = app( GoogleConnectionResolver::class );

    expect( $first )->toBe( $second );
} );

it( 'registers the main GoogleSearchConsole facade accessor', function (): void {
    expect( app( 'google-search-console' ) )->toBeInstanceOf( GoogleSearchConsole::class );
} );

it( 'contributes the webmasters.readonly scope to the ScopeRegistry', function (): void {
    BaseInstalled::setForTesting( true );

    $registry = app( ScopeRegistry::class );

    expect( $registry->all() )->toContain( 'https://www.googleapis.com/auth/webmasters.readonly' );
} );

it( 'has reporting when base is installed', function (): void {
    BaseInstalled::setForTesting( true );

    $gsc = app( 'google-search-console' );

    expect( $gsc->hasReporting() )->toBeTrue();
    expect( $gsc->client() )->toBeInstanceOf( SearchAnalyticsClient::class );
} );

it( 'registers routes when base is installed', function (): void {
    BaseInstalled::setForTesting( true );

    expect( app( 'router' )->has( 'google-search-console.performance' ) )->toBeTrue();
    expect( app( 'router' )->has( 'google-search-console.top-queries' ) )->toBeTrue();
    expect( app( 'router' )->has( 'google-search-console.top-pages' ) )->toBeTrue();
} );
