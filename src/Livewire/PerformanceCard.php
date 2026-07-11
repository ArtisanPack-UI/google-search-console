<?php

/**
 * Search Console performance card Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Livewire;

use ArtisanPackUI\Google\Models\GoogleConnection;
use ArtisanPackUI\GoogleSearchConsole\Exceptions\BaseNotInstalledException;
use ArtisanPackUI\GoogleSearchConsole\Exceptions\ReportingException;
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewData;
use ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

/**
 * Renders the Search Console performance card: four headline metrics
 * (clicks, impressions, avg CTR, avg position) and a daily trend chart
 * for a configurable date range.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class PerformanceCard extends Component
{
    public int $days = 28;

    public ?string $siteUrl = null;

    /**
     * Loaded totals; null while the component has never fetched or has
     * encountered an error.
     *
     * @var array<string, float>|null
     */
    public ?array $totals = null;

    /**
     * @var list<array{date: string, clicks: float, impressions: float, ctr: float, position: float}>
     */
    public array $trend = [];

    public ?string $errorMessage = null;

    public bool $baseInstalled = true;

    /**
     * False when the API returned no rows for the current range. Drives
     * the empty-state UI so a brand-new property doesn't render as if it
     * has real zero-click traffic.
     */
    public bool $hasData = false;

    public function mount( int $days = 28, ?string $siteUrl = null ): void
    {
        $this->days          = $this->clampDays( $days );
        $this->siteUrl       = $siteUrl;
        $this->baseInstalled = BaseInstalled::check();

        if ( ! $this->baseInstalled ) {
            return;
        }

        $this->refresh();
    }

    public function updatedDays( int $value ): void
    {
        $this->days = $this->clampDays( $value );
        $this->refresh();
    }

    public function refresh(): void
    {
        $this->errorMessage = null;

        if ( ! BaseInstalled::check() ) {
            $this->baseInstalled = false;

            return;
        }

        try {
            $connection = $this->resolveConnection();

            if ( null === $connection ) {
                $this->errorMessage = __( 'Connect a Google account to view Search Console data.' );

                return;
            }

            /** @var SearchAnalyticsClient $client */
            $client = app( SearchAnalyticsClient::class );

            $fetcher = new PerformanceOverviewFetcher( $client );
            $data    = $fetcher->fetch( $connection, DateRange::lastDays( $this->days ), $this->siteUrl );

            $this->applyData( $data );
        } catch ( BaseNotInstalledException ) {
            $this->baseInstalled = false;
        } catch ( ReportingException $e ) {
            $this->errorMessage = $e->getMessage();
        } catch ( Throwable $e ) {
            $this->errorMessage = __( 'Could not load Search Console data: :message', [ 'message' => $e->getMessage() ] );
        }
    }

    public function render(): View
    {
        return view( 'google-search-console::livewire.performance-card' );
    }

    protected function clampDays( int $days ): int
    {
        return max( 1, min( $days, DateRange::MAX_DAYS ) );
    }

    protected function resolveConnection(): ?GoogleConnection
    {
        return app( GoogleConnectionResolver::class )->forUser( auth()->user() );
    }

    protected function applyData( PerformanceOverviewData $data ): void
    {
        $this->totals  = $data->totals;
        $this->trend   = $data->trend;
        $this->hasData = $data->hasData;
    }
}
