<?php

/**
 * GoogleSearchConsole service provider.
 *
 * Bootstraps the Search Console package: registers the shared
 * SearchAnalyticsClient, contributes required OAuth scopes to the
 * shared google base package, registers Livewire components, and
 * loads the HTTP routes that back the React and Vue components.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole;

use ArtisanPackUI\Google\Tokens\TokenManager;
use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\PerformanceCardWidget;
use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\TopPagesTableWidget;
use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\TopQueriesTableWidget;
use ArtisanPackUI\GoogleSearchConsole\Livewire\PerformanceCard;
use ArtisanPackUI\GoogleSearchConsole\Livewire\TopPagesTable;
use ArtisanPackUI\GoogleSearchConsole\Livewire\TopQueriesTable;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\CmsFrameworkInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the GoogleSearchConsole package.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class GoogleSearchConsoleServiceProvider extends ServiceProvider
{
    /**
     * @since 1.0.0
     */
    public function register(): void
    {
        $this->mergeConfigFrom( __DIR__ . '/../config/google-search-console.php', 'google-search-console' );

        $this->app->singleton(
            GoogleConnectionResolver::class,
            fn (): GoogleConnectionResolver => new GoogleConnectionResolver(),
        );

        $this->app->singleton( SearchAnalyticsClient::class, fn ( Application $app ): SearchAnalyticsClient => new SearchAnalyticsClient(
            $app[ 'config' ],
            $app->make( HttpFactory::class ),
            BaseInstalled::check() ? $app->make( TokenManager::class ) : null,
            $app->make( 'cache.store' ),
        ) );

        $this->app->singleton( 'google-search-console', function ( Application $app ): GoogleSearchConsole {
            return new GoogleSearchConsole(
                BaseInstalled::check() ? $app->make( SearchAnalyticsClient::class ) : null,
            );
        } );
    }

    /**
     * @since 1.0.0
     */
    public function boot(): void
    {
        $this->publishes( [
            __DIR__ . '/../config/google-search-console.php' => config_path( 'google-search-console.php' ),
        ], 'google-search-console-config' );

        $this->publishes( [
            __DIR__ . '/../resources/views' => resource_path( 'views/vendor/google-search-console' ),
        ], 'google-search-console-views' );

        $this->publishes( [
            __DIR__ . '/../resources/js' => resource_path( 'js/vendor/google-search-console' ),
        ], 'google-search-console-js' );

        $this->loadViewsFrom( __DIR__ . '/../resources/views', 'google-search-console' );

        $this->registerRoutes();
        $this->registerGoogleScopeHook();
        $this->registerLivewireComponents();
        $this->registerCmsFrameworkWidgets();
    }

    /**
     * The CMS-framework admin widget types this package contributes,
     * as `type => widget class` pairs. Exposed as a static method so
     * tests can exercise the registration list without booting the
     * service provider.
     *
     * @since 1.0.0
     *
     * @return array<string, class-string>
     */
    public static function cmsFrameworkWidgetTypeMap(): array
    {
        return [
            'google-search-console.performance-card'  => PerformanceCardWidget::class,
            'google-search-console.top-queries-table' => TopQueriesTableWidget::class,
            'google-search-console.top-pages-table'   => TopPagesTableWidget::class,
        ];
    }

    /**
     * Load the HTTP routes when the base is installed. The routes hit
     * the GoogleConnection model so we cannot register them without
     * the base package on the autoloader.
     *
     * @since 1.0.0
     */
    protected function registerRoutes(): void
    {
        if ( ! BaseInstalled::check() ) {
            return;
        }

        if ( false === (bool) $this->app[ 'config' ]->get( 'google-search-console.routes.enabled', true ) ) {
            return;
        }

        Route::group( [
            'prefix'     => (string) $this->app[ 'config' ]->get( 'google-search-console.routes.prefix', 'google-search-console' ),
            'middleware' => (array) $this->app[ 'config' ]->get( 'google-search-console.routes.middleware', [ 'web', 'auth' ] ),
        ], function (): void {
            $this->loadRoutesFrom( __DIR__ . '/../routes/web.php' );
        } );
    }

    /**
     * Contribute the webmasters.readonly scope to the base google
     * package's ScopeRegistry via the `ap.google.scopes` filter hook so
     * the single-consent screen covers Search Console alongside every
     * other Google service.
     *
     * Skips silently if the hooks helpers or the base package are
     * missing — the package still boots on hosts without them.
     *
     * @since 1.0.0
     */
    protected function registerGoogleScopeHook(): void
    {
        if ( ! BaseInstalled::check() ) {
            return;
        }

        if ( ! function_exists( 'addFilter' ) ) {
            return;
        }

        $config = $this->app[ 'config' ];

        addFilter( 'ap.google.scopes', static function ( array $scopes ) use ( $config ): array {
            $ours = (array) $config->get( 'google-search-console.scopes', [] );

            return array_values( array_unique( array_merge( $scopes, array_map( 'strval', $ours ) ) ) );
        } );
    }

    /**
     * Register Livewire components when Livewire is installed. Livewire
     * is an optional peer — apps without it can still use the React and
     * Vue components without penalty.
     *
     * @since 1.0.0
     */
    protected function registerLivewireComponents(): void
    {
        if ( ! class_exists( \Livewire\Livewire::class ) ) {
            return;
        }

        \Livewire\Livewire::component(
            'google-search-console::performance-card',
            PerformanceCard::class,
        );

        \Livewire\Livewire::component(
            'google-search-console::top-queries-table',
            TopQueriesTable::class,
        );

        \Livewire\Livewire::component(
            'google-search-console::top-pages-table',
            TopPagesTable::class,
        );
    }

    /**
     * Register the three Livewire components as CMS-framework admin
     * dashboard widgets. This is an optional bridge — the wrapper
     * classes are only referenced when both the CMS framework and
     * Livewire are installed, so the package stays CMS-agnostic when
     * they are absent.
     *
     * The Livewire component aliases mirror the base component
     * registration so the CMS framework can render the widgets by
     * class name or by the `google-search-console::*-widget` alias.
     *
     * @since 1.0.0
     */
    protected function registerCmsFrameworkWidgets(): void
    {
        if ( ! CmsFrameworkInstalled::check() ) {
            return;
        }

        if ( ! class_exists( \Livewire\Livewire::class ) ) {
            return;
        }

        $manager = $this->app->make(
            \ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services\AdminWidgetManager::class,
        );

        foreach ( self::cmsFrameworkWidgetTypeMap() as $type => $class ) {
            $manager->register( $type, $class );
        }

        \Livewire\Livewire::component(
            'google-search-console::performance-card-widget',
            PerformanceCardWidget::class,
        );

        \Livewire\Livewire::component(
            'google-search-console::top-queries-table-widget',
            TopQueriesTableWidget::class,
        );

        \Livewire\Livewire::component(
            'google-search-console::top-pages-table-widget',
            TopPagesTableWidget::class,
        );
    }
}
