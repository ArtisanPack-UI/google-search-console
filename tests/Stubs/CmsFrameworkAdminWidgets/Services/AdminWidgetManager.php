<?php

/**
 * Local test stub for the CMS framework's AdminWidgetManager.
 *
 * Mirrors the register / getAvailableWidgets shape of the real manager
 * so tests can assert that the bridge registers our three widgets
 * correctly without pulling the full cms-framework package into
 * `require-dev`.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services;

use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface;

class AdminWidgetManager
{
    /**
     * @var array<string, class-string<AdminWidgetInterface>>
     */
    protected array $widgets = [];

    /**
     * @param  class-string  $class
     */
    public function register( string $type, string $class ): void
    {
        if ( in_array( AdminWidgetInterface::class, class_implements( $class ), true ) ) {
            $this->widgets[ $type ] = $class;
        }
    }

    /**
     * @return array<string, array{
     *     title: string,
     *     description: string,
     *     capability?: string,
     *     default_options?: array<string, mixed>
     * }>
     */
    public function getAvailableWidgets(): array
    {
        $available = [];

        foreach ( $this->widgets as $type => $class ) {
            $available[ $type ] = $class::getWidgetInfo();
        }

        return $available;
    }

    /**
     * @return array<string, class-string<AdminWidgetInterface>>
     */
    public function getRegistered(): array
    {
        return $this->widgets;
    }
}
