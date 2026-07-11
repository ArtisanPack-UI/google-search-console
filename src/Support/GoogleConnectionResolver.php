<?php

/**
 * Shared GoogleConnection resolver.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Support;

use ArtisanPackUI\Google\Models\GoogleConnection;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Single source of truth for looking up the GoogleConnection the
 * Search Console reporting surfaces should use.
 *
 * Applications with multi-site or tenant-scoped needs can override the
 * container binding for this class to have all surfaces pick up the
 * change together.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class GoogleConnectionResolver
{
    /**
     * Return the most recently updated connected account for the given
     * user, or null when the user is unauthenticated, the base package
     * is not installed, or the user has no active connection.
     *
     * @since 1.0.0
     */
    public function forUser( ?Authenticatable $user ): ?GoogleConnection
    {
        if ( null === $user || ! class_exists( GoogleConnection::class ) ) {
            return null;
        }

        return GoogleConnection::query()
            ->where( 'user_id', $user->getAuthIdentifier() )
            ->where( 'status', GoogleConnection::STATUS_CONNECTED )
            ->orderByDesc( 'updated_at' )
            ->first();
    }
}
