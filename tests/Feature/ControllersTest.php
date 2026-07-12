<?php

declare( strict_types=1 );

use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsRequest;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsResponse;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;
use Illuminate\Contracts\Auth\Authenticatable;

beforeEach( function (): void {
    BaseInstalled::setForTesting( true );

    config()->set( 'google-search-console.reporting.site_url', 'https://configured.example/' );
} );

it( 'ignores a user-supplied ?site_url and uses the configured value', function (): void {
    // Stub the resolver to return a synthetic connection so the controller
    // reaches the fetcher; stub the client so we can inspect what site
    // URL it actually gets passed.
    app()->instance( GoogleConnectionResolver::class, new class extends GoogleConnectionResolver {
        public function forUser( ?Authenticatable $user ): ?ArtisanPackUI\Google\Models\GoogleConnection
        {
            return makeGscConnectedConnection();
        }
    } );

    $capturedSiteUrl = null;
    app()->instance( SearchAnalyticsClient::class, new class( $capturedSiteUrl ) extends SearchAnalyticsClient {
        // phpcs:ignore
        public function __construct( public ?string &$capturedSiteUrl )
        {
        }

        public function query(
            SearchAnalyticsRequest $request,
            ArtisanPackUI\Google\Models\GoogleConnection $connection,
            ?string $siteUrl = null,
        ): SearchAnalyticsResponse {
            $this->capturedSiteUrl = $siteUrl;

            return new SearchAnalyticsResponse( [ 'rows' => [] ], $request->dimensions );
        }
    } );

    $user = new class implements Authenticatable {
        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier()
        {
            return 1;
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): string
        {
            return '';
        }

        public function getRememberToken(): string
        {
            return '';
        }

        public function setRememberToken( $value ): void
        {
        }

        public function getRememberTokenName(): string
        {
            return '';
        }
    };

    $response = $this->actingAs( $user )
        ->getJson( '/google-search-console/performance?site_url=https://attacker.example/&days=7' );

    $response->assertOk();

    // The attacker-supplied override must have been ignored — the client
    // must have been called with $siteUrl = null so it falls back to the
    // configured site_url in config.
    expect( app( SearchAnalyticsClient::class )->capturedSiteUrl )->toBeNull();
} );
