<?php

/**
 * Performance card admin widget bridge for the CMS framework.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets;

use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface;
use ArtisanPackUI\GoogleSearchConsole\Livewire\PerformanceCard;

/**
 * Exposes the PerformanceCard Livewire component to the CMS framework's
 * dashboard as a registerable admin widget.
 *
 * Extending the underlying Livewire component lets the CMS framework
 * render this class the same way as any other Livewire-backed widget
 * while satisfying the AdminWidgetInterface metadata contract.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class PerformanceCardWidget extends PerformanceCard implements AdminWidgetInterface
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
            'title'           => __( 'Search performance' ),
            'description'     => __( 'Search Console clicks, impressions, CTR, and position for a rolling range.' ),
            'capability'      => 'view_google_search_console',
            'default_options' => [
                'days'    => 28,
                'siteUrl' => null,
            ],
        ];
    }
}
