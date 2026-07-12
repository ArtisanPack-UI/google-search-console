<?php

/**
 * Exception raised when a Search Console API request fails.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Wraps low-level HTTP or configuration failures encountered while
 * talking to the Search Console API into a single typed exception
 * callers can catch and surface to their users.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
class ReportingException extends RuntimeException
{
    /**
     * Raised when a required configuration value is missing.
     *
     * @since 1.0.0
     */
    public static function missingConfiguration( string $key ): self
    {
        return new self( __( 'Search Console configuration ":key" is missing.', [ 'key' => $key ] ) );
    }

    /**
     * Raised when no connected Google account is available to authenticate
     * the request.
     *
     * @since 1.0.0
     */
    public static function missingConnection(): self
    {
        return new self( __( 'No connected Google account was resolved for the Search Console request.' ) );
    }

    /**
     * Raised when the Search Console API returned a non-2xx response.
     *
     * @since 1.0.0
     */
    public static function apiError( int $status, string $body ): self
    {
        return new self( __( 'Search Console API returned an error (status :status): :body', [
            'status' => $status,
            'body'   => $body,
        ] ) );
    }

    /**
     * Raised when the OAuth token could not be refreshed / is no longer
     * valid — surfaced separately so callers can prompt the user to
     * reconnect the Google account instead of blaming the API.
     *
     * @since 1.0.0
     */
    public static function authenticationFailed( Throwable $previous ): self
    {
        return new self(
            __( 'Reconnect your Google account to continue viewing Search Console data.' ),
            0,
            $previous,
        );
    }

    /**
     * Raised when the API call itself could not be delivered
     * (connection refused, DNS failure, timeout).
     *
     * @since 1.0.0
     */
    public static function transportFailure( Throwable $previous ): self
    {
        return new self(
            __( 'Could not reach the Search Console API: :message', [ 'message' => $previous->getMessage() ] ),
            0,
            $previous,
        );
    }
}
