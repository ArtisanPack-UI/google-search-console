<?php

/**
 * GoogleSearchConsole package helper functions.
 *
 * Global helper functions for the GoogleSearchConsole package.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

use ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsole;

if ( ! function_exists( 'googleSearchConsole' ) ) {
    /**
     * Get the GoogleSearchConsole instance.
     *
     * @since 1.0.0
     *
     * @return GoogleSearchConsole
     */
    function googleSearchConsole(): GoogleSearchConsole
    {
        return app( 'google-search-console' );
    }
}

// Add your custom helper functions below
