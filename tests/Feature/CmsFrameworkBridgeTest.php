<?php

declare( strict_types=1 );

use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface;
use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services\AdminWidgetManager;
use ArtisanPackUI\Google\Models\GoogleConnection;
use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\PerformanceCardWidget;
use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\TopPagesTableWidget;
use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\TopQueriesTableWidget;
use ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsoleServiceProvider;
use ArtisanPackUI\GoogleSearchConsole\Livewire\PerformanceCard;
use ArtisanPackUI\GoogleSearchConsole\Livewire\TopPagesTable;
use ArtisanPackUI\GoogleSearchConsole\Livewire\TopQueriesTable;
use ArtisanPackUI\GoogleSearchConsole\Reporting\TopPagesFetcher;
use ArtisanPackUI\GoogleSearchConsole\Reporting\TopQueriesFetcher;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\CmsFrameworkInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it( 'each widget wrapper extends the corresponding Livewire component so the CMS can render it', function (): void {
    expect( is_subclass_of( PerformanceCardWidget::class, PerformanceCard::class ) )->toBeTrue();
    expect( is_subclass_of( TopQueriesTableWidget::class, TopQueriesTable::class ) )->toBeTrue();
    expect( is_subclass_of( TopPagesTableWidget::class, TopPagesTable::class ) )->toBeTrue();
} );

it( 'each widget wrapper implements the CMS AdminWidgetInterface contract', function (): void {
    expect( is_subclass_of( PerformanceCardWidget::class, AdminWidgetInterface::class ) )->toBeTrue();
    expect( is_subclass_of( TopQueriesTableWidget::class, AdminWidgetInterface::class ) )->toBeTrue();
    expect( is_subclass_of( TopPagesTableWidget::class, AdminWidgetInterface::class ) )->toBeTrue();
} );

it( 'exposes a widget type map covering all three widget types wired to the correct wrappers', function (): void {
    expect( GoogleSearchConsoleServiceProvider::cmsFrameworkWidgetTypeMap() )->toBe( [
        'google-search-console.performance-card'  => PerformanceCardWidget::class,
        'google-search-console.top-queries-table' => TopQueriesTableWidget::class,
        'google-search-console.top-pages-table'   => TopPagesTableWidget::class,
    ] );
} );

it( 'PerformanceCardWidget metadata pins the exact translated title, description, capability, and defaults', function (): void {
    $info = PerformanceCardWidget::getWidgetInfo();

    expect( $info )->toHaveKeys( [ 'title', 'description', 'capability', 'default_options' ] );
    expect( $info[ 'title' ] )->toBe( 'Search performance' );
    expect( $info[ 'description' ] )
        ->toBe( 'Search Console clicks, impressions, CTR, and position for a rolling range.' );
    expect( $info[ 'capability' ] )->toBe( 'view_google_search_console' );
    expect( $info[ 'default_options' ] )->toBe( [
        'days'    => 28,
        'siteUrl' => null,
    ] );
} );

it( 'TopQueriesTableWidget metadata pins the exact translated title, description, capability, and defaults', function (): void {
    $info = TopQueriesTableWidget::getWidgetInfo();

    expect( $info[ 'title' ] )->toBe( 'Top queries' );
    expect( $info[ 'description' ] )
        ->toBe( 'Highest-performing search queries from Search Console for a rolling range.' );
    expect( $info[ 'capability' ] )->toBe( 'view_google_search_console' );
    expect( $info[ 'default_options' ] )->toBe( [
        'days'    => 28,
        'limit'   => TopQueriesFetcher::DEFAULT_LIMIT,
        'siteUrl' => null,
    ] );
} );

it( 'TopPagesTableWidget metadata pins the exact translated title, description, capability, and defaults', function (): void {
    $info = TopPagesTableWidget::getWidgetInfo();

    expect( $info[ 'title' ] )->toBe( 'Top pages' );
    expect( $info[ 'description' ] )
        ->toBe( 'Highest-performing pages in Search Console for a rolling range.' );
    expect( $info[ 'capability' ] )->toBe( 'view_google_search_console' );
    expect( $info[ 'default_options' ] )->toBe( [
        'days'    => 28,
        'limit'   => TopPagesFetcher::DEFAULT_LIMIT,
        'siteUrl' => null,
    ] );
} );

it( 'the singleton AdminWidgetManager has all three widgets registered after boot, and no more than three', function (): void {
    // The initial Testbench boot ran with CmsFrameworkInstalled::check()
    // returning true (the stubs are autoloaded), so the SP's boot() hooked
    // its widget map into the container-bound manager. TestCase binds the
    // manager as a singleton so we see the exact same instance boot did.
    $registered = app( AdminWidgetManager::class )->getRegistered();

    expect( $registered )->toBe( [
        'google-search-console.performance-card'  => PerformanceCardWidget::class,
        'google-search-console.top-queries-table' => TopQueriesTableWidget::class,
        'google-search-console.top-pages-table'   => TopPagesTableWidget::class,
    ] );

    // Guards against a non-idempotent boot leaking duplicates.
    expect( $registered )->toHaveCount( 3 );
} );

it( 'AdminWidgetManager.getAvailableWidgets returns pinned metadata for every registered widget', function (): void {
    $available = app( AdminWidgetManager::class )->getAvailableWidgets();

    expect( array_keys( $available ) )->toEqual( [
        'google-search-console.performance-card',
        'google-search-console.top-queries-table',
        'google-search-console.top-pages-table',
    ] );

    expect( $available[ 'google-search-console.performance-card' ][ 'title' ] )->toBe( 'Search performance' );
    expect( $available[ 'google-search-console.top-queries-table' ][ 'title' ] )->toBe( 'Top queries' );
    expect( $available[ 'google-search-console.top-pages-table' ][ 'title' ] )->toBe( 'Top pages' );
} );

it( 'registerCmsFrameworkWidgets short-circuits when the CMS framework is absent, leaving the manager untouched', function (): void {
    CmsFrameworkInstalled::setForTesting( false );

    // Replace the container-bound singleton with a fresh empty manager
    // so we can prove the guard prevented any new registrations.
    $manager = new AdminWidgetManager();
    $this->app->instance( AdminWidgetManager::class, $manager );

    // Invoke ONLY the guarded method (not the full boot) so we prove
    // the guard shortcircuits without side-effecting scope hooks, routes,
    // or other Livewire aliases. Using reflection because the method is
    // deliberately protected — this is the one place we look through it.
    $reflection = new ReflectionMethod( GoogleSearchConsoleServiceProvider::class, 'registerCmsFrameworkWidgets' );
    $reflection->setAccessible( true );
    $reflection->invoke( new GoogleSearchConsoleServiceProvider( $this->app ) );

    expect( $manager->getRegistered() )->toBe( [] );
} );

it( 'PerformanceCardWidget mounts through Livewire and renders the performance UI just like the base component', function (): void {
    BaseInstalled::setForTesting( true );
    config()->set( 'google-search-console.reporting.site_url', 'https://example.com/' );
    stubGscBindings( $this->app );

    Http::fakeSequence()
        ->push( [
            'rows' => [
                [ 'keys' => [], 'clicks' => 137, 'impressions' => 900, 'ctr' => 0.152, 'position' => 4.1 ],
            ],
        ], 200 )
        ->push( [
            'rows' => [
                [ 'keys' => [ '2026-01-01' ], 'clicks' => 137, 'impressions' => 900, 'ctr' => 0.152, 'position' => 4.1 ],
            ],
        ], 200 );

    Livewire::test( PerformanceCardWidget::class )
        ->assertSet( 'baseInstalled', true )
        ->assertSet( 'hasData', true )
        ->assertSet( 'totals.clicks', 137.0 )
        ->assertSee( number_format( 137 ) )
        ->assertSee( 'Search performance' );
} );

it( 'TopQueriesTableWidget mounts through Livewire and renders the same rows as the base component', function (): void {
    BaseInstalled::setForTesting( true );
    config()->set( 'google-search-console.reporting.site_url', 'https://example.com/' );
    stubGscBindings( $this->app );

    Http::fake( [
        '*' => Http::response( [
            'rows' => [
                [ 'keys' => [ 'widget-query-alpha' ], 'clicks' => 88, 'impressions' => 400, 'ctr' => 0.22, 'position' => 2 ],
            ],
        ], 200 ),
    ] );

    Livewire::test( TopQueriesTableWidget::class )
        ->assertSet( 'baseInstalled', true )
        ->assertSee( 'widget-query-alpha' );
} );

it( 'TopPagesTableWidget mounts through Livewire and renders the same rows as the base component', function (): void {
    BaseInstalled::setForTesting( true );
    config()->set( 'google-search-console.reporting.site_url', 'https://example.com/' );
    stubGscBindings( $this->app );

    Http::fake( [
        '*' => Http::response( [
            'rows' => [
                [ 'keys' => [ '/widget-page-alpha' ], 'clicks' => 77, 'impressions' => 350, 'ctr' => 0.22, 'position' => 3 ],
            ],
        ], 200 ),
    ] );

    Livewire::test( TopPagesTableWidget::class )
        ->assertSet( 'baseInstalled', true )
        ->assertSee( '/widget-page-alpha' );
} );

/**
 * Bind the resolver + client stubs shared by the widget-mount tests.
 * Extracted so we don't repeat the same setup in three places.
 */
function stubGscBindings( Illuminate\Contracts\Foundation\Application $app ): void
{
    $app->instance( GoogleConnectionResolver::class, new class extends GoogleConnectionResolver {
        public function forUser( ?Authenticatable $user ): ?GoogleConnection
        {
            return makeGscConnectedConnection();
        }
    } );

    $app->instance(
        ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient::class,
        new ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient(
            config: $app[ 'config' ],
            http: $app->make( Illuminate\Http\Client\Factory::class ),
            tokens: makeGscStubTokenManager( 'test-access-token' ),
        ),
    );
}
