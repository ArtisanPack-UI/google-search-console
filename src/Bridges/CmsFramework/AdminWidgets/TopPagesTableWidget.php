<?php

/**
 * Top pages table admin widget bridge for the CMS framework.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets;

use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface;
use ArtisanPackUI\GoogleSearchConsole\Livewire\TopPagesTable;
use ArtisanPackUI\GoogleSearchConsole\Reporting\TopPagesFetcher;

/**
 * Exposes the TopPagesTable Livewire component to the CMS framework's
 * dashboard as a registerable admin widget.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class TopPagesTableWidget extends TopPagesTable implements AdminWidgetInterface
{
    /**
     * Metadata used by the CMS framework's "Add Widget" panel and by
     * `AdminWidgetManager::createWidget()` to seed default options.
     *
     * @since 1.0.0
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     capability: string,
     *     default_options: array<string, mixed>
     * }
     */
    public static function getWidgetInfo(): array
    {
        return [
            'title'           => __( 'Top pages' ),
            'description'     => __( 'Highest-performing pages in Search Console for a rolling range.' ),
            'capability'      => 'view_google_search_console',
            'default_options' => [
                'days'    => 28,
                'limit'   => TopPagesFetcher::DEFAULT_LIMIT,
                'siteUrl' => null,
            ],
        ];
    }
}
