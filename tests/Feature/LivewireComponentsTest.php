<?php

declare( strict_types=1 );

use ArtisanPackUI\Google\Models\GoogleConnection;
use ArtisanPackUI\GoogleSearchConsole\Livewire\PerformanceCard;
use ArtisanPackUI\GoogleSearchConsole\Livewire\TopPagesTable;
use ArtisanPackUI\GoogleSearchConsole\Livewire\TopQueriesTable;
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach( function (): void {
    BaseInstalled::setForTesting( true );

    config()->set( 'google-search-console.reporting.site_url', 'https://example.com/' );

    // Bind a resolver stub so the components see a connected account
    // regardless of the (test-only) auth guard.
    app()->instance( GoogleConnectionResolver::class, new class extends GoogleConnectionResolver {
        public function forUser( ?Authenticatable $user ): ?GoogleConnection
        {
            return makeGscConnectedConnection();
        }
    } );

    // Both the real TokenManager and the base package's models are only
    // needed when the client actually issues a request. Bind the client
    // singleton against a stub TokenManager so mounts do not blow up.
    app()->instance(
        ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient::class,
        new ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient(
            config: app( 'config' ),
            http: app( Illuminate\Http\Client\Factory::class ),
            tokens: makeGscStubTokenManager( 'test-access-token' ),
        ),
    );
} );

it( 'PerformanceCard mounts with a populated totals + trend from a mocked API response', function (): void {
    Http::fakeSequence()
        ->push( [
            'rows' => [
                [ 'keys' => [], 'clicks' => 42, 'impressions' => 500, 'ctr' => 0.084, 'position' => 3.2 ],
            ],
        ], 200 )
        ->push( [
            'rows' => [
                [ 'keys' => [ '2026-01-01' ], 'clicks' => 42, 'impressions' => 500, 'ctr' => 0.084, 'position' => 3.2 ],
            ],
        ], 200 );

    Livewire::test( PerformanceCard::class )
        ->assertSet( 'days', 28 )
        ->assertSet( 'baseInstalled', true )
        ->assertSet( 'hasData', true )
        ->assertSet( 'errorMessage', null )
        ->assertSee( 'Search performance' )
        ->assertSee( 'Clicks' );
} );

it( 'PerformanceCard clamps the days input to the configured maximum', function (): void {
    Http::fake( [ '*' => Http::response( [ 'rows' => [] ], 200 ) ] );

    Livewire::test( PerformanceCard::class, [ 'days' => 9999 ] )
        ->assertSet( 'days', DateRange::MAX_DAYS );
} );

it( 'PerformanceCard flips baseInstalled to false when the base package is missing', function (): void {
    BaseInstalled::setForTesting( false );

    Livewire::test( PerformanceCard::class )
        ->assertSet( 'baseInstalled', false )
        ->assertSee( 'requires the base google package' );
} );

it( 'TopQueriesTable mounts, hydrates rows, and renders the header + a query row', function (): void {
    Http::fake( [
        '*' => Http::response( [
            'rows' => [
                [ 'keys' => [ 'buy shoes' ],  'clicks' => 50, 'impressions' => 200, 'ctr' => 0.25, 'position' => 2 ],
                [ 'keys' => [ 'cheap shoes' ], 'clicks' => 20, 'impressions' => 100, 'ctr' => 0.2, 'position' => 4 ],
            ],
        ], 200 ),
    ] );

    Livewire::test( TopQueriesTable::class )
        ->assertSet( 'baseInstalled', true )
        ->assertSet( 'errorMessage', null )
        ->assertSee( 'buy shoes' )
        ->assertSee( 'cheap shoes' );
} );

it( 'TopQueriesTable sorts by column and flips direction on second click', function (): void {
    Http::fake( [
        '*' => Http::response( [
            'rows' => [
                [ 'keys' => [ 'a' ], 'clicks' => 5,  'impressions' => 50, 'ctr' => 0.1, 'position' => 6 ],
                [ 'keys' => [ 'b' ], 'clicks' => 50, 'impressions' => 200, 'ctr' => 0.25, 'position' => 2 ],
            ],
        ], 200 ),
    ] );

    $component = Livewire::test( TopQueriesTable::class );

    $component->call( 'sortByColumn', 'impressions' )
        ->assertSet( 'sortBy', 'impressions' )
        ->assertSet( 'sortDir', 'desc' );

    $component->call( 'sortByColumn', 'impressions' )
        ->assertSet( 'sortDir', 'asc' );
} );

it( 'TopQueriesTable ignores an unknown sort column and does not mutate state', function (): void {
    Http::fake( [ '*' => Http::response( [ 'rows' => [] ], 200 ) ] );

    Livewire::test( TopQueriesTable::class )
        ->call( 'sortByColumn', 'not-a-real-column' )
        ->assertSet( 'sortBy', 'clicks' )
        ->assertSet( 'sortDir', 'desc' );
} );

it( 'TopPagesTable mounts, hydrates rows, and renders a page row', function (): void {
    Http::fake( [
        '*' => Http::response( [
            'rows' => [
                [ 'keys' => [ '/pricing' ], 'clicks' => 33, 'impressions' => 300, 'ctr' => 0.11, 'position' => 4 ],
                [ 'keys' => [ '/blog' ],    'clicks' => 15, 'impressions' => 150, 'ctr' => 0.1, 'position' => 6 ],
            ],
        ], 200 ),
    ] );

    Livewire::test( TopPagesTable::class )
        ->assertSet( 'baseInstalled', true )
        ->assertSet( 'errorMessage', null )
        ->assertSee( '/pricing' )
        ->assertSee( '/blog' );
} );

it( 'TopPagesTable pagination advances and clamps at the last page', function (): void {
    $rows = [];
    for ( $i = 0; $i < 25; $i++ ) {
        $rows[] = [ 'keys' => [ '/p-' . $i ], 'clicks' => 25 - $i, 'impressions' => 100, 'ctr' => 0.1, 'position' => 4 ];
    }

    Http::fake( [ '*' => Http::response( [ 'rows' => $rows ], 200 ) ] );

    $component = Livewire::test( TopPagesTable::class )
        ->assertSet( 'page', 1 );

    $component->call( 'nextPage' )
        ->assertSet( 'page', 2 )
        ->call( 'nextPage' )
        ->assertSet( 'page', 3 );

    // Only 25 rows / 10 per page = 3 pages total. A fourth nextPage
    // must clamp at 3 rather than overshoot.
    $component->call( 'nextPage' )
        ->assertSet( 'page', 3 );

    $component->call( 'previousPage' )
        ->assertSet( 'page', 2 );
} );

it( 'TopPagesTable renders the missing-base call to action when base is absent', function (): void {
    BaseInstalled::setForTesting( false );

    Livewire::test( TopPagesTable::class )
        ->assertSet( 'baseInstalled', false )
        ->assertSee( 'top pages require the base google package' );
} );
