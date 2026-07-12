<?php

/**
 * Data object backing the Search Console performance card.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Reporting;

/**
 * Shape of the payload each performance overview surface (Livewire,
 * React, Vue) consumes.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
final class PerformanceOverviewData
{
    /**
     * @param  array{clicks: float, impressions: float, ctr: float, position: float}  $totals
     * @param  list<array{date: string, clicks: float, impressions: float, ctr: float, position: float}>  $trend
     * @param  bool  $hasData  False when the API returned no rows for the range.
     *                        Consumers should render an empty state instead of
     *                        the zero-value tiles that $totals would otherwise show.
     */
    public function __construct(
        public readonly array $totals,
        public readonly array $trend,
        public readonly DateRange $dateRange,
        public readonly bool $hasData,
    ) {
    }

    /**
     * Convert into the JSON shape expected by the React/Vue components.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'range'    => $this->dateRange->toArray(),
            'totals'   => $this->totals,
            'trend'    => $this->trend,
            'has_data' => $this->hasData,
        ];
    }
}
