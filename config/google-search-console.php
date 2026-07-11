<?php

/**
 * GoogleSearchConsole package configuration.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

return [

    /*
    |--------------------------------------------------------------------------
    | Server-side reporting (Search Console API)
    |--------------------------------------------------------------------------
    |
    | Talks to the Search Console API using the OAuth token held by the base
    | google package. Requires `artisanpack-ui/google` to be installed.
    |
    */

    'reporting' => [

        // The site URL (verified property) that the Search Console API
        // queries target. Domain properties use the "sc-domain:" prefix
        // (e.g. "sc-domain:example.com"), URL properties use the full
        // URL (e.g. "https://example.com/").
        'site_url' => env( 'GSC_SITE_URL' ),

        // Base URL for the Search Console API. searchAnalytics.query and
        // sites both live under the legacy /webmasters/v3 prefix even
        // though the host is searchconsole.googleapis.com. Only the URL
        // Inspection API is under /v1.
        'api_base' => 'https://searchconsole.googleapis.com/webmasters/v3',

        // Request timeout in seconds for API calls.
        'timeout' => 30,

        // How long (in seconds) to cache API responses. Set to 0 to
        // disable caching.
        'cache_ttl' => 300,

    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth scopes
    |--------------------------------------------------------------------------
    |
    | The Google OAuth scopes this package requires. Contributed to the
    | shared google base package's ScopeRegistry via the `ap.google.scopes`
    | filter hook so consent covers Search Console alongside every other
    | Google service the app uses.
    |
    */

    'scopes' => [
        'https://www.googleapis.com/auth/webmasters.readonly',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP routes
    |--------------------------------------------------------------------------
    |
    | Configures the endpoints that back the React and Vue components. When
    | 'enabled' is false the routes are not registered, which is the correct
    | choice for headless / API-only apps.
    |
    */

    'routes' => [

        'enabled'    => true,
        'prefix'     => 'google-search-console',
        'middleware' => [ 'web', 'auth' ],

    ],

];
