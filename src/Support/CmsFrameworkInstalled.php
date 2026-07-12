<?php

/**
 * Presence check for the `artisanpack-ui/cms-framework` package.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Support;

/**
 * Cache-friendly detector for whether the CMS framework package is
 * installed. Used to gate the optional AdminWidget bridge so the
 * package remains bootable when the CMS framework is absent.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
final class CmsFrameworkInstalled
{
    /**
     * Cached result so repeated calls do not walk the autoloader.
     */
    private static ?bool $cached = null;

    /**
     * Return true when the CMS framework's AdminWidget entry points are
     * both loadable.
     *
     * @since 1.0.0
     */
    public static function check(): bool
    {
        if ( null !== self::$cached ) {
            return self::$cached;
        }

        return self::$cached = interface_exists( \ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface::class )
            && class_exists( \ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services\AdminWidgetManager::class );
    }

    /**
     * Reset the memoized result. Only used by tests.
     *
     * @since 1.0.0
     */
    public static function reset(): void
    {
        self::$cached = null;
    }

    /**
     * Force the memoized result to a specific value. Test-only helper
     * for exercising the "cms framework not installed" branch without
     * literally uninstalling the package from the autoloader.
     *
     * @since 1.0.0
     */
    public static function setForTesting( ?bool $value ): void
    {
        self::$cached = $value;
    }
}
