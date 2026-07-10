<?php

/**
 * GoogleSearchConsole service provider.
 *
 * Bootstraps the Search Console package by registering the container
 * binding for the main class. Relies on the shared google base package
 * for OAuth2, token, and scope services.
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

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the GoogleSearchConsole package.
 *
 * Binds the main GoogleSearchConsole class and boots the Search
 * Console reporting surface. Add configuration publishing,
 * migrations, routes, and Livewire component registration here as the
 * package grows.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */
class GoogleSearchConsoleServiceProvider extends ServiceProvider
{
    /**
     * Registers any application services.
     *
     * Binds the GoogleSearchConsole class as a singleton in the
     * container.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton( 'google-search-console', function ( $app ) {
            return new GoogleSearchConsole();
        } );
    }

    /**
     * Bootstraps any application services.
     *
     * Add package bootstrapping here such as:
     * - Configuration publishing: $this->publishes([...])
     * - Migration loading: $this->loadMigrationsFrom(...)
     * - Route loading: $this->loadRoutesFrom(...)
     * - Livewire component registration
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot(): void
    {
        // Add your package bootstrapping here
    }
}
