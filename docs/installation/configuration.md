---
title: Configuration
---

# Configuration

Full reference for `config/google-search-console.php`. Publish with:

```bash
php artisan vendor:publish --tag=google-search-console-config
```

The default config file is short and every value has a sane default — you can typically get away with only setting `GSC_SITE_URL` in `.env`.

## `reporting`

Search Console API settings. Everything under this key belongs to the `SearchAnalyticsClient` and the three fetchers.

### `reporting.site_url`

The **verified Search Console property** the API queries target.

- **Env**: `GSC_SITE_URL` (required)
- **Default**: `null`
- **Type**: `string`

Formats:

| Property type | Example |
|---|---|
| URL property | `https://example.com/` (trailing slash required) |
| Domain property | `sc-domain:example.com` (no scheme, no slash) |

The string must literally match one of the properties [Google Search Console](https://search.google.com/search-console) lists for the connected account. See [[Installation/Property Verification|property verification]].

**Multi-tenant apps** should not set this via env — override the `SearchAnalyticsClient` binding in a tenant-scoped middleware / provider so each tenant queries its own property. See [[Reporting#Multi-tenant apps]].

### `reporting.api_base`

Base URL for the Search Console API.

- **Default**: `https://searchconsole.googleapis.com/webmasters/v3`
- **Type**: `string`

Change only if Google reorganises the endpoint. `searchAnalytics.query` and `sites` both live under the legacy `/webmasters/v3` prefix; only the URL Inspection API is under `/v1`, and this package doesn't call it.

### `reporting.timeout`

HTTP request timeout in seconds.

- **Default**: `30`
- **Type**: `int`
- **Falls back** to `30` when set to `0`, `null`, or any non-positive value — Guzzle treats `timeout(0)` as "no timeout", which would hang the worker on a stuck endpoint.

### `reporting.cache_ttl`

How long (in seconds) to cache successful API responses. Set to `0` to disable caching.

- **Default**: `300`
- **Type**: `int`

Cache keys are per-connection-per-query-payload — distinct users cannot see each other's rows.

## `scopes`

The Google OAuth scopes this package contributes to the shared registry.

- **Default**: `[ 'https://www.googleapis.com/auth/webmasters.readonly' ]`
- **Type**: `array<int, string>`

Registered via the `ap.google.scopes` filter hook. Rarely worth editing — the read-only Search Console scope is the whole point of the package. See [[Scopes]].

## `routes`

HTTP routes that back the React and Vue components.

### `routes.enabled`

Whether to register the three HTTP routes at all.

- **Default**: `true`
- **Type**: `bool`

Set to `false` for headless / API-only apps that only use the server-side [[Reporting|reporting]] API.

### `routes.prefix`

URL prefix for the three routes.

- **Default**: `google-search-console`
- **Type**: `string`

### `routes.middleware`

Middleware stack applied to the three routes.

- **Default**: `[ 'web', 'auth' ]`
- **Type**: `array<int, string>`

`auth` is mandatory in practice — the controllers pull the connection off `$request->user()`. If you drop `auth`, resolve the connection some other way in a middleware before the controllers run.

## Full example

```php
// config/google-search-console.php
return [
    'reporting' => [
        'site_url'  => env( 'GSC_SITE_URL' ),
        'api_base'  => 'https://searchconsole.googleapis.com/webmasters/v3',
        'timeout'   => 30,
        'cache_ttl' => 300,
    ],

    'scopes' => [
        'https://www.googleapis.com/auth/webmasters.readonly',
    ],

    'routes' => [
        'enabled'    => true,
        'prefix'     => 'google-search-console',
        'middleware' => [ 'web', 'auth' ],
    ],
];
```

## Runtime overrides

Every value is read at runtime — no compilation step, no boot-time freeze. `config()->set()` from a test, a middleware, or a service provider takes effect on the next `SearchAnalyticsClient` call.

The `SearchAnalyticsClient` is registered as a **singleton**, so if you want to swap the client itself (not just its config) per tenant, you need to `->instance()` a fresh client into the container — see [[Reporting#Multi-tenant apps]].
