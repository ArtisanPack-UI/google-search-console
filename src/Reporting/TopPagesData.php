<?php

/**
 * Data object backing the Search Console top pages table.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Reporting;

/**
 * Shape of the payload the top pages surfaces consume.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
final class TopPagesData
{
    /**
     * @param  list<array{page: string, clicks: float, impressions: float, ctr: float, position: float}>  $rows
     */
    public function __construct(
        public readonly array $rows,
        public readonly DateRange $dateRange,
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
            'range' => $this->dateRange->toArray(),
            'rows'  => $this->rows,
        ];
    }
}
