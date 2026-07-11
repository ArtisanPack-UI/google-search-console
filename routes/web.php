<?php

/**
 * GoogleSearchConsole package HTTP routes.
 *
 * Backs the React and Vue components. Loaded from the service provider
 * under the configurable route prefix so host apps can move / disable
 * them without editing the package.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\GoogleSearchConsole\Http\Controllers\PerformanceOverviewController;
use ArtisanPackUI\GoogleSearchConsole\Http\Controllers\TopPagesController;
use ArtisanPackUI\GoogleSearchConsole\Http\Controllers\TopQueriesController;
use Illuminate\Support\Facades\Route;

Route::get( 'performance', PerformanceOverviewController::class )
    ->name( 'google-search-console.performance' );

Route::get( 'top-queries', TopQueriesController::class )
    ->name( 'google-search-console.top-queries' );

Route::get( 'top-pages', TopPagesController::class )
    ->name( 'google-search-console.top-pages' );
