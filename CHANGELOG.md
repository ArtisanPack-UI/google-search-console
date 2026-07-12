# ArtisanPack UI GoogleSearchConsole Changelog

## [Unreleased]

## [1.0.0] - 2026-07-11

Initial release. Search Console performance data and drop-in UI components on top of the shared `artisanpack-ui/google` OAuth2 + token management layer.

### Added

- **`SearchAnalyticsClient`** — typed wrapper over Google's `searchAnalytics.query` endpoint, with per-connection-per-query cache keys, config-driven timeout (guards against Guzzle's `timeout(0)` = "no timeout" footgun), and well-typed exceptions (`BaseNotInstalledException`, `ReportingException`).
- **Three high-level fetchers**:
  - `PerformanceOverviewFetcher` — two queries (totals + daily trend). `hasData` distinguishes "empty" from "all zeros" so fresh properties don't render misleading zero-metric cards.
  - `TopQueriesFetcher` — top queries by clicks. `DEFAULT_LIMIT = 50`.
  - `TopPagesFetcher` — top pages by clicks. `DEFAULT_LIMIT = 50`.
- **Typed value objects** — `DateRange`, `SearchAnalyticsRequest`, `SearchAnalyticsResponse`, `PerformanceOverviewData`, `TopQueriesData`, `TopPagesData`. Every DTO exposes a `toArray()` matching the HTTP endpoints' JSON shape.
- **Three Livewire components** — `PerformanceCard`, `TopQueriesTable`, `TopPagesTable`. Auto-registered under `google-search-console::*` aliases when Livewire is installed. Handle missing-base state, unconnected state, empty state, and API errors uniformly.
- **Three React components** — `PerformanceCard`, `TopQueriesTable`, `TopPagesTable`. Ship as source under `resources/js/react/`. Use a shared TypeScript fetch client (`resources/js/shared/`) so both React and Vue talk to the same server payload shape.
- **Three Vue 3 components** — mirror the React API. `resources/js/vue/`.
- **Three JSON HTTP endpoints** backing the React and Vue components — `GET /performance`, `GET /top-queries`, `GET /top-pages`. Mounted under a configurable prefix (default `/google-search-console`) with `web` + `auth` middleware. Deliberately ignore any user-supplied `?site_url` override so tenants can't cross-query.
- **OAuth scope contribution** — `webmasters.readonly` is contributed to the base `artisanpack-ui/google` package's shared `ScopeRegistry` via the `ap.google.scopes` filter hook. Single consent screen covers Search Console alongside every other Google service.
- **Optional CMS framework bridge** — three widget wrappers (`PerformanceCardWidget`, `TopQueriesTableWidget`, `TopPagesTableWidget`) under `src/Bridges/CmsFramework/AdminWidgets/` that register with `artisanpack-ui/cms-framework`'s `AdminWidgetManager` when the framework is installed. Each wrapper extends its underlying Livewire component and implements `AdminWidgetInterface`, so shipped views + styles carry through to CMS-hosted widgets. Widget-type map exposed as `GoogleSearchConsoleServiceProvider::cmsFrameworkWidgetTypeMap()` for testability. Bridge is dormant when either dependency is absent.
- **`CmsFrameworkInstalled` support class** — presence detector mirroring `BaseInstalled` (memoised per-process, resettable in tests, `setForTesting()` for controlled state).
- **`BaseInstalled` support class** — memoised detector for `artisanpack-ui/google`.
- **`GoogleConnectionResolver`** — user → connected `GoogleConnection` mapping. Rebindable per tenant.
- **Cache layer** — `SearchAnalyticsClient` caches successful responses via Laravel's default cache store (`google-search-console.reporting.cache_ttl` seconds, default `300`). Per-connection, per-query cache keys prevent cross-tenant contamination.
- **Publishable assets** — `google-search-console-config`, `google-search-console-views`, `google-search-console-js`.
- **Comprehensive test suite** — 61 tests across `SearchAnalyticsClient`, fetchers, controllers, Livewire component rendering (mount, empty state, sort order, pagination, missing-base state), and the CMS bridge (using local stubs autoloaded into the real cms-framework namespace so the bridge is exercised without pulling in the framework's transitive deps).
- **Comprehensive `/docs` tree** — installation, components (Livewire / React / Vue / custom), HTTP endpoints, reporting API, scopes, CMS bridge, API reference, testing, FAQ, troubleshooting.
- **Laravel 10, 11, 12, and 13 support** via widened `illuminate/support` constraint.
- **CI + Release workflows** — GitHub Actions matrix testing PHP 8.2/8.3/8.4 × Laravel 12/13, plus a tag-driven release workflow with Packagist push.

### Security

- Controllers ignore any user-supplied `?site_url` query string. The site URL comes from config only — accepting a caller-supplied override would let one authenticated user query any property the shared Google account owns.
- Cache keys hash the connection ID + query payload so distinct users cannot see each other's cached rows.
- The `SearchAnalyticsClient` timeout falls back to 30 seconds when the config value is `null` / `0` / non-numeric — a stray unset env var can't hang the worker on a stuck endpoint.

### Notes

- No new database migrations. All persisted data (`google_connections` tokens) lives in the base package's tables.
- No secrets stored by this package. `GSC_SITE_URL` is public information.
