<?php

/**
 * Search Console searchAnalytics.query API client.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Reporting;

use ArtisanPackUI\Google\Exceptions\TokenRefreshException;
use ArtisanPackUI\Google\Models\GoogleConnection;
use ArtisanPackUI\Google\Tokens\TokenManager;
use ArtisanPackUI\GoogleSearchConsole\Exceptions\BaseNotInstalledException;
use ArtisanPackUI\GoogleSearchConsole\Exceptions\ReportingException;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

/**
 * Server-side client for the Search Console API's
 * searchAnalytics.query endpoint.
 *
 * Runs requests against a configured site (verified property) using an
 * access token from the base google package's TokenManager. Callers
 * should catch {@see ReportingException} for API/config errors and
 * {@see BaseNotInstalledException} for the case where the base package
 * is missing.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class SearchAnalyticsClient
{
    public function __construct(
        protected ConfigRepository $config,
        protected HttpFactory $http,
        protected ?TokenManager $tokens = null,
        protected ?CacheRepository $cache = null,
    ) {
    }

    /**
     * Execute a searchAnalytics.query request against the configured
     * site. Returns a typed {@see SearchAnalyticsResponse} DTO, not the
     * raw JSON array.
     *
     * @since 1.0.0
     *
     * @param  SearchAnalyticsRequest  $request  The report parameters.
     * @param  GoogleConnection  $connection  A connected Google account
     *                                        authorized for the site.
     * @param  string|null  $siteUrl  Optional site override; falls back
     *                                to the configured site URL.
     *
     * @throws BaseNotInstalledException
     * @throws ReportingException
     */
    public function query(
        SearchAnalyticsRequest $request,
        GoogleConnection $connection,
        ?string $siteUrl = null,
    ): SearchAnalyticsResponse {
        $this->ensureBaseInstalled();

        $site = (string) ( $siteUrl ?? $this->config->get( 'google-search-console.reporting.site_url' ) ?? '' );

        if ( '' === $site ) {
            throw ReportingException::missingConfiguration( 'google-search-console.reporting.site_url' );
        }

        if ( null === $this->tokens ) {
            throw BaseNotInstalledException::forReporting();
        }

        $payload  = $request->toApiPayload();
        $cacheKey = $this->cacheKey( $connection, $site, $payload );
        $cacheTtl = (int) $this->config->get( 'google-search-console.reporting.cache_ttl', 0 );

        if ( $cacheTtl > 0 && null !== $this->cache && $this->cache->has( $cacheKey ) ) {
            $cached = $this->cache->get( $cacheKey );

            if ( is_array( $cached ) ) {
                return new SearchAnalyticsResponse( $cached, $request->dimensions );
            }
        }

        try {
            $accessToken = $this->tokens->getValidAccessToken( $connection );
        } catch ( TokenRefreshException $e ) {
            throw ReportingException::authenticationFailed( $e );
        }

        $apiBase = (string) $this->config->get(
            'google-search-console.reporting.api_base',
            'https://searchconsole.googleapis.com/webmasters/v3',
        );

        $endpoint = sprintf(
            '%s/sites/%s/searchAnalytics/query',
            rtrim( $apiBase, '/' ),
            rawurlencode( $site ),
        );
        // Fall back to 30s when the config value is missing, null, or non-positive.
        // Guzzle treats timeout(0) as "no timeout" — a stray `env('GSC_TIMEOUT')`
        // with the var unset would otherwise hang the worker on a stuck endpoint.
        $timeoutRaw = $this->config->get( 'google-search-console.reporting.timeout', 30 );
        $timeout    = is_numeric( $timeoutRaw ) && (int) $timeoutRaw > 0 ? (int) $timeoutRaw : 30;

        try {
            $response = $this->http
                ->timeout( $timeout )
                ->withToken( $accessToken )
                ->acceptJson()
                ->asJson()
                ->post( $endpoint, $payload );
        } catch ( ConnectionException $e ) {
            throw ReportingException::transportFailure( $e );
        }

        if ( ! $response->successful() ) {
            throw ReportingException::apiError( $response->status(), (string) $response->body() );
        }

        $body = $response->json();
        $body = is_array( $body ) ? $body : [];

        if ( $cacheTtl > 0 && null !== $this->cache ) {
            $this->cache->put( $cacheKey, $body, $cacheTtl );
        }

        return new SearchAnalyticsResponse( $body, $request->dimensions );
    }

    /**
     * Whether the reporting side is usable in the current environment.
     *
     * @since 1.0.0
     */
    public function isAvailable(): bool
    {
        return BaseInstalled::check() && null !== $this->tokens;
    }

    /**
     * Deterministic cache key for a (connection, site, payload) tuple.
     * The connection identity is included so distinct users cannot see
     * each other's cached rows.
     *
     * @param  array<string, mixed>  $payload
     *
     * @since 1.0.0
     */
    protected function cacheKey( GoogleConnection $connection, string $site, array $payload ): string
    {
        $connectionId = (string) ( $connection->getKey() ?? $connection->google_user_id ?? 'anon' );
        $hash         = hash( 'sha256', $site . '|' . $connectionId . '|' . json_encode( $payload ) );

        return 'google-search-console:query:' . $hash;
    }

    /**
     * Throw when the base package's public surface is not loadable.
     */
    protected function ensureBaseInstalled(): void
    {
        if ( ! BaseInstalled::check() ) {
            throw BaseNotInstalledException::forReporting();
        }
    }
}
