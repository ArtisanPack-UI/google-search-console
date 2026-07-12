---
title: Environment Variables
---

# Environment Variables

The package reads exactly one env var. Everything else lives in `config/google-search-console.php` (see [Configuration](Installation-Configuration)).

## `GSC_SITE_URL`

- **Required**
- Read by: `reporting.site_url` in the config file.
- **Type**: `string`
- **No default** — must be set for the reporting side to work.

The verified Search Console property this app queries.

```env
# URL property — trailing slash required
GSC_SITE_URL="https://example.com/"

# Domain property — sc-domain: prefix, no scheme, no slash
GSC_SITE_URL="sc-domain:example.com"
```

The string must literally match a property [Google Search Console](https://search.google.com/search-console) reports for the connected account. Trailing slashes, `http` vs `https`, and the `sc-domain:` prefix all matter. See [property verification](Installation-Property-Verification).

For multi-tenant apps this env var is a footgun — one config value can't scope per-tenant safely. Override the `SearchAnalyticsClient` binding in your tenant middleware instead ([Reporting](Reporting#multi-tenant-apps)).

## Env vars this package does not use

The following env vars are read by dependent packages, not this one — listing them here to save a source dive:

| Env var | Belongs to | Purpose |
|---|---|---|
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` / `GOOGLE_REDIRECT_URI` | [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) | The OAuth client credentials. |
| `GOOGLE_CONFIG_DRIVER` | [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) | Which credential driver stores the OAuth client. |
| `APP_KEY` | Laravel | Encrypts the access/refresh tokens on `google_connections`. |
| `CACHE_STORE` (or `CACHE_DRIVER` on older Laravel) | Laravel | Backing store for the `SearchAnalyticsClient` response cache. |
| `SESSION_DRIVER` | Laravel | Session store for the base package's OAuth flow. Not used by this package. |

If you're seeing a "credentials are not configured" error at `/google/auth/connect`, that's the base package — see the base package's [FAQ](https://github.com/ArtisanPack-UI/google/blob/main/docs/faq.md).
