<?php

/**
 * GoogleSearchConsole Facade.
 *
 * Provides static access to the GoogleSearchConsole class.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * GoogleSearchConsole Facade.
 *
 * @see \ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsole
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */
class GoogleSearchConsole extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'google-search-console';
    }
}
