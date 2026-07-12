<?php

use ArtisanPackUI\Google\Models\GoogleConnection;
use ArtisanPackUI\Google\Tokens\TokenManager;

pest()->extend( Tests\TestCase::class )
    ->in( 'Feature' );

expect()->extend( 'toBeOne', function () {
    return $this->toBe( 1 );
} );

if ( ! function_exists( 'makeGscStubTokenManager' ) ) {
    /**
     * Returns a TokenManager anonymous stub that yields the given token
     * regardless of the connection passed in.
     */
    function makeGscStubTokenManager( string $token ): TokenManager
    {
        return new class( $token ) extends TokenManager {
            public function __construct( private string $token )
            {
            }

            public function getValidAccessToken( GoogleConnection $connection ): string
            {
                return $this->token;
            }
        };
    }
}

if ( ! function_exists( 'makeGscThrowingTokenManager' ) ) {
    function makeGscThrowingTokenManager( Throwable $exception ): TokenManager
    {
        return new class( $exception ) extends TokenManager {
            public function __construct( private Throwable $exception )
            {
            }

            public function getValidAccessToken( GoogleConnection $connection ): string
            {
                throw $this->exception;
            }
        };
    }
}

if ( ! function_exists( 'makeGscConnectedConnection' ) ) {
    function makeGscConnectedConnection(): GoogleConnection
    {
        $connection                 = new GoogleConnection();
        $connection->id             = 1;
        $connection->user_id        = 1;
        $connection->google_user_id = 'test-user';
        $connection->email          = 'test@example.com';
        $connection->status         = GoogleConnection::STATUS_CONNECTED;
        $connection->exists         = true;

        return $connection;
    }
}
