---
title: Property Verification
---

# Property Verification

The Search Console API only returns data for **verified properties** the connected Google account has permission on. The single most common reason "the component shows an error" is that `GSC_SITE_URL` doesn't match a real verified property for this account.

## Verify a property in Search Console

1. Open [Google Search Console](https://search.google.com/search-console).
2. Click the property picker (top-left) → **Add property**.
3. Choose the property type:
   - **URL prefix** — verify `https://example.com/` (each scheme + subdomain is a separate property).
   - **Domain** — verify `example.com` (covers every scheme and subdomain via DNS TXT).
4. Complete the verification handshake (HTML file upload, HTML meta tag, DNS TXT record, Google Analytics, or Google Tag Manager).
5. Once verified, the property appears in the picker for the connected account.

## Choose the right `GSC_SITE_URL` string

Search Console reports properties in a specific string format that the API demands verbatim.

| Property type in Search Console | `GSC_SITE_URL` value |
|---|---|
| URL prefix: `https://example.com/` | `GSC_SITE_URL="https://example.com/"` (trailing slash **required**) |
| URL prefix: `https://www.example.com/` | `GSC_SITE_URL="https://www.example.com/"` (`www` matters) |
| URL prefix: `http://example.com/` | `GSC_SITE_URL="http://example.com/"` (`http` vs `https` matters) |
| Domain: `example.com` | `GSC_SITE_URL="sc-domain:example.com"` (`sc-domain:` prefix, no scheme, no slash) |

If the string is wrong, the API returns **404**. Small differences (missing trailing slash, `www.` prefix, `http` vs `https`) all count as different properties from Google's point of view.

## Debug: list the properties Google reports for this account

The safest way to pick a `GSC_SITE_URL` value is to ask Google. Hit `sites.list` with the connected account's token:

```php
use ArtisanPackUI\Google\Facades\Google;
use Illuminate\Support\Facades\Http;

$token = Google::tokens()->getValidAccessToken( $connection );

$response = Http::withToken( $token )
    ->acceptJson()
    ->get( 'https://searchconsole.googleapis.com/webmasters/v3/sites' );

dump( $response->json() );
```

The response looks like:

```json
{
  "siteEntry": [
    { "siteUrl": "https://example.com/",         "permissionLevel": "siteOwner" },
    { "siteUrl": "sc-domain:example.com",        "permissionLevel": "siteOwner" },
    { "siteUrl": "https://staging.example.com/", "permissionLevel": "siteRestrictedUser" }
  ]
}
```

Copy one of the `siteUrl` values **verbatim** into `.env`, then run `php artisan config:clear`.

- `permissionLevel` must be `siteOwner` or `siteFullUser` for `searchAnalytics.query` to return rows. `siteRestrictedUser` may work with the URL Inspection API but not with performance queries.

## Debug route in a dev app

The `artisanpack-ui-dev` app in this repo ships a debug endpoint that runs the above snippet. See `/gsc-test/sites-list` in `resources/views/gsc-test.blade.php` — the same pattern makes a good add for any host app during setup:

```php
Route::get( '/gsc-test/sites-list', function () {
    // ... snippet above ...
} );
```

The route returns the raw API response plus your current `GSC_SITE_URL` value so you can compare directly.

## Common failure modes

### API returns 404 with "The specified site does not have the requested permission"

`GSC_SITE_URL` doesn't match any verified property the connected account can query. Compare against the `sites.list` response above.

### API returns 403 with "User does not have sufficient permission"

The property is verified but the account only has `siteRestrictedUser`. Grant `siteOwner` or `siteFullUser` in Search Console → **Settings → Users and permissions**.

### API returns rows on day 1 but zero rows on day 8

This happens with **brand-new properties**. Search Console needs about 48–72 hours to start populating data, and the last 2–3 days of any range are usually withheld while Google finalises them. The `PerformanceOverviewFetcher` distinguishes "no rows at all" (`hasData = false`) from "all-zero totals" — the components render an empty state rather than a misleading zero-clicks card.

### URL property works but Domain property doesn't (or vice versa)

They're separate properties. Verifying `https://example.com/` doesn't give you data for `https://www.example.com/` — either verify both as URL properties, or verify the root as a Domain property.
