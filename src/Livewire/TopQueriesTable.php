<?php

/**
 * Search Console top queries table Livewire component.
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
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Reporting\TopQueriesData;
use ArtisanPackUI\GoogleSearchConsole\Reporting\TopQueriesFetcher;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

/**
 * Renders the Search Console top queries table with server-fetched
 * rows and client-side sort + paginate over the visible page.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class TopQueriesTable extends Component
{
    public int $days = 28;

    public int $limit = TopQueriesFetcher::DEFAULT_LIMIT;

    public ?string $siteUrl = null;

    public string $sortBy = 'clicks';

    public string $sortDir = 'desc';

    public int $perPage = 10;

    public int $page = 1;

    /**
     * @var list<array{query: string, clicks: float, impressions: float, ctr: float, position: float}>
     */
    public array $rows = [];

    public ?string $errorMessage = null;

    public bool $baseInstalled = true;

    public function mount( int $days = 28, int $limit = TopQueriesFetcher::DEFAULT_LIMIT, ?string $siteUrl = null ): void
    {
        $this->days          = $this->clampDays( $days );
        $this->limit         = $this->clampLimit( $limit );
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
        $this->page = 1;
        $this->refresh();
    }

    public function sortByColumn( string $column ): void
    {
        $allowed = [ 'query', 'clicks', 'impressions', 'ctr', 'position' ];

        if ( ! in_array( $column, $allowed, true ) ) {
            return;
        }

        if ( $this->sortBy === $column ) {
            $this->sortDir = 'asc' === $this->sortDir ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = 'position' === $column || 'query' === $column ? 'asc' : 'desc';
        }

        $this->page = 1;
    }

    public function nextPage(): void
    {
        $totalPages = $this->totalPages();

        if ( $this->page < $totalPages ) {
            $this->page++;
        }
    }

    public function previousPage(): void
    {
        if ( $this->page > 1 ) {
            $this->page--;
        }
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

            $fetcher = new TopQueriesFetcher( $client );
            $data    = $fetcher->fetch( $connection, DateRange::lastDays( $this->days ), $this->siteUrl, $this->limit );

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
        return view( 'google-search-console::livewire.top-queries-table', [
            'visibleRows' => $this->visibleRows(),
            'totalPages'  => $this->totalPages(),
        ] );
    }

    /**
     * @return list<array{query: string, clicks: float, impressions: float, ctr: float, position: float}>
     */
    protected function visibleRows(): array
    {
        $sorted = $this->sortRows( $this->rows );
        $offset = ( $this->page - 1 ) * $this->perPage;

        return array_slice( $sorted, $offset, $this->perPage );
    }

    protected function totalPages(): int
    {
        return max( 1, (int) ceil( count( $this->rows ) / max( 1, $this->perPage ) ) );
    }

    /**
     * @param  list<array<string, float|string>>  $rows
     *
     * @return list<array<string, float|string>>
     */
    protected function sortRows( array $rows ): array
    {
        $column = $this->sortBy;
        $dir    = 'asc' === $this->sortDir ? 1 : -1;

        usort( $rows, static function ( array $a, array $b ) use ( $column, $dir ): int {
            $left  = $a[ $column ] ?? 0;
            $right = $b[ $column ] ?? 0;

            if ( is_numeric( $left ) && is_numeric( $right ) ) {
                return ( (float) $left <=> (float) $right ) * $dir;
            }

            return strcmp( (string) $left, (string) $right ) * $dir;
        } );

        return array_values( $rows );
    }

    protected function clampDays( int $days ): int
    {
        return max( 1, min( $days, DateRange::MAX_DAYS ) );
    }

    protected function clampLimit( int $limit ): int
    {
        return max( 1, min( $limit, 1000 ) );
    }

    protected function resolveConnection(): ?GoogleConnection
    {
        return app( GoogleConnectionResolver::class )->forUser( auth()->user() );
    }

    protected function applyData( TopQueriesData $data ): void
    {
        $this->rows = $data->rows;
    }
}
