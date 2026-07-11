<?php

declare( strict_types=1 );

use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface;
use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services\AdminWidgetManager;
use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\PerformanceCardWidget;
use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\TopPagesTableWidget;
use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\TopQueriesTableWidget;
use ArtisanPackUI\GoogleSearchConsole\Livewire\PerformanceCard;
use ArtisanPackUI\GoogleSearchConsole\Livewire\TopPagesTable;
use ArtisanPackUI\GoogleSearchConsole\Livewire\TopQueriesTable;
use ArtisanPackUI\GoogleSearchConsole\Support\CmsFrameworkInstalled;

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

it( 'PerformanceCardWidget metadata includes translated title, description, capability, and defaults', function (): void {
    $info = PerformanceCardWidget::getWidgetInfo();

    expect( $info )->toHaveKeys( [ 'title', 'description', 'capability', 'default_options' ] );
    expect( $info[ 'title' ] )->toBeString()->not->toBe( '' );
    expect( $info[ 'description' ] )->toBeString()->not->toBe( '' );
    expect( $info[ 'capability' ] )->toBe( 'view_google_search_console' );
    expect( $info[ 'default_options' ] )->toHaveKey( 'days' );
    expect( $info[ 'default_options' ][ 'days' ] )->toBe( 28 );
} );

it( 'TopQueriesTableWidget seeds default days + limit that match the Livewire defaults', function (): void {
    $info = TopQueriesTableWidget::getWidgetInfo();

    expect( $info[ 'default_options' ][ 'days' ] )->toBe( 28 );
    expect( $info[ 'default_options' ][ 'limit' ] )
        ->toBe( ArtisanPackUI\GoogleSearchConsole\Reporting\TopQueriesFetcher::DEFAULT_LIMIT );
} );

it( 'TopPagesTableWidget seeds default days + limit that match the Livewire defaults', function (): void {
    $info = TopPagesTableWidget::getWidgetInfo();

    expect( $info[ 'default_options' ][ 'days' ] )->toBe( 28 );
    expect( $info[ 'default_options' ][ 'limit' ] )
        ->toBe( ArtisanPackUI\GoogleSearchConsole\Reporting\TopPagesFetcher::DEFAULT_LIMIT );
} );

it( 'the service provider registers all three widgets with the CMS AdminWidgetManager when the framework is installed', function (): void {
    CmsFrameworkInstalled::setForTesting( true );

    $manager = new AdminWidgetManager();
    $this->app->instance( AdminWidgetManager::class, $manager );

    // Re-boot the package's service provider so registerCmsFrameworkWidgets()
    // sees the manager binding we just replaced.
    ( new ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsoleServiceProvider( $this->app ) )->boot();

    $registered = $manager->getRegistered();

    expect( $registered )->toHaveKey( 'google-search-console.performance-card' );
    expect( $registered )->toHaveKey( 'google-search-console.top-queries-table' );
    expect( $registered )->toHaveKey( 'google-search-console.top-pages-table' );

    expect( $registered[ 'google-search-console.performance-card' ] )->toBe( PerformanceCardWidget::class );
    expect( $registered[ 'google-search-console.top-queries-table' ] )->toBe( TopQueriesTableWidget::class );
    expect( $registered[ 'google-search-console.top-pages-table' ] )->toBe( TopPagesTableWidget::class );
} );

it( 'the service provider does NOT touch the CMS AdminWidgetManager when the framework is absent', function (): void {
    CmsFrameworkInstalled::setForTesting( false );

    $manager = new AdminWidgetManager();
    $this->app->instance( AdminWidgetManager::class, $manager );

    ( new ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsoleServiceProvider( $this->app ) )->boot();

    expect( $manager->getRegistered() )->toBe( [] );
} );

it( 'AdminWidgetManager.getAvailableWidgets returns metadata for every registered widget', function (): void {
    CmsFrameworkInstalled::setForTesting( true );

    $manager = new AdminWidgetManager();
    $this->app->instance( AdminWidgetManager::class, $manager );

    ( new ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsoleServiceProvider( $this->app ) )->boot();

    $available = $manager->getAvailableWidgets();

    expect( array_keys( $available ) )->toEqual( [
        'google-search-console.performance-card',
        'google-search-console.top-queries-table',
        'google-search-console.top-pages-table',
    ] );

    foreach ( $available as $info ) {
        expect( $info )->toHaveKeys( [ 'title', 'description', 'capability', 'default_options' ] );
    }
} );
