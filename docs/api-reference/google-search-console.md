---
title: GoogleSearchConsole
---

# `GoogleSearchConsole`

The facade / helper root. FQCN: `ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsole`. Registered as a singleton under the string binding `'google-search-console'`.

## Constructor

```php
public function __construct(
    protected ?SearchAnalyticsClient $client = null,
)
```

Instantiated once by the service provider with the `SearchAnalyticsClient` singleton — or `null` when the base `artisanpack-ui/google` package isn't installed.

## Methods

### `client(): ?SearchAnalyticsClient`

The Search Console API client. Returns `null` when `hasReporting()` is `false` — callers can feature-detect without catching an exception:

```php
if ( $client = googleSearchConsole()->client() ) {
    $response = $client->query( $request, $connection );
}
```

### `hasReporting(): bool`

Whether the reporting side is usable in the current environment. `true` when both the constructor received a non-null client AND `BaseInstalled::check()` is currently true.

Used as a gate in every callsite that wants to bail cleanly on missing infra rather than catch a `BaseNotInstalledException`:

```php
if ( ! googleSearchConsole()->hasReporting() ) {
    return view( 'dashboard.no-gsc-yet' );
}
```

## The facade

`ArtisanPackUI\GoogleSearchConsole\Facades\GoogleSearchConsole` extends `Illuminate\Support\Facades\Facade` and returns `'google-search-console'` from `getFacadeAccessor()`. Every facade call goes through the singleton `GoogleSearchConsole` instance.

## The helper

```php
function googleSearchConsole(): GoogleSearchConsole
{
    return app( 'google-search-console' );
}
```

Same singleton as the facade.

## See also

- [[API Reference/Search Analytics Client]]
- [[API Reference/Support]]
