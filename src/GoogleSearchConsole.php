<?php

/**
 * Main GoogleSearchConsole class.
 *
 * Entry point for Search Console performance data and UI. Uses the
 * shared google base package for OAuth2 and token management. Accessed
 * via the `googleSearchConsole()` helper function or the
 * GoogleSearchConsole facade.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole;

use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;

/**
 * Convenience aggregator for the GoogleSearchConsole services.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class GoogleSearchConsole
{
    public function __construct(
        protected ?SearchAnalyticsClient $client = null,
    ) {
    }

    /**
     * The Search Console API client. Available only when the base
     * google package is installed; otherwise returns null so callers
     * can feature-detect rather than catching an exception.
     *
     * @since 1.0.0
     */
    public function client(): ?SearchAnalyticsClient
    {
        if ( ! $this->hasReporting() ) {
            return null;
        }

        return $this->client;
    }

    /**
     * Whether the reporting side is usable in the current environment.
     *
     * @since 1.0.0
     */
    public function hasReporting(): bool
    {
        return null !== $this->client && BaseInstalled::check();
    }
}
