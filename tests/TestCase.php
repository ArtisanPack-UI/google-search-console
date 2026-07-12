<?php

declare( strict_types=1 );

namespace Tests;

use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services\AdminWidgetManager;
use ArtisanPackUI\Google\GoogleServiceProvider;
use ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsoleServiceProvider;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use ArtisanPackUI\GoogleSearchConsole\Support\CmsFrameworkInstalled;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Base test case for the GoogleSearchConsole package.
 *
 * @since 1.0.0
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        BaseInstalled::reset();
        CmsFrameworkInstalled::reset();
    }

    protected function tearDown(): void
    {
        BaseInstalled::reset();
        CmsFrameworkInstalled::reset();

        parent::tearDown();
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders( $app ): array
    {
        return [
            LivewireServiceProvider::class,
            GoogleServiceProvider::class,
            GoogleSearchConsoleServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment( $app ): void
    {
        $app[ 'config' ]->set( 'app.key', 'base64:' . base64_encode( random_bytes( 32 ) ) );

        $app[ 'config' ]->set( 'database.default', 'testbench' );
        $app[ 'config' ]->set( 'database.connections.testbench', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ] );

        // Bind the CMS framework's AdminWidgetManager as a shared instance
        // BEFORE providers boot. The real cms-framework's ServiceProvider
        // does the same in production; without this binding, `app()->make()`
        // would hand back a fresh (empty) manager for every lookup, so
        // widgets registered during boot would be invisible to tests.
        $app->singleton( AdminWidgetManager::class );
    }
}
