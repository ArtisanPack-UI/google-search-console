---
title: Changelog
---

# Changelog

The authoritative changelog lives at `CHANGELOG.md` in the package root. This page mirrors it.

## [Unreleased]

### Added

- **Optional CMS framework bridge** — three widget wrappers (`PerformanceCardWidget`, `TopQueriesTableWidget`, `TopPagesTableWidget`) under `src/Bridges/CmsFramework/AdminWidgets/` that register with the `artisanpack-ui/cms-framework` `AdminWidgetManager` when both the framework and Livewire are installed. Each wrapper extends its underlying Livewire component and implements `AdminWidgetInterface`, so the shipped views + styles carry through to the CMS-hosted widgets. Widget-type map exposed as `GoogleSearchConsoleServiceProvider::cmsFrameworkWidgetTypeMap()` for testability. Bridge remains dormant when either dep is absent — package stays CMS-agnostic.
- **`CmsFrameworkInstalled` support class** — presence detector mirroring `BaseInstalled` (memoised per-process, resettable in tests, `setForTesting()` for controlled state).
- **Rendering test suite** — nine new tests in `tests/Feature/LivewireComponentsTest.php` cover mount, empty state, sort order (with `assertSeeInOrder`), pagination (with row-visibility assertions), and missing-base rendering for all three Livewire components. Adversarial: assertions pin numeric values from mocked responses so a fetcher regression fails red.
- **CMS bridge test suite** — twelve new tests in `tests/Feature/CmsFrameworkBridgeTest.php`. Uses local stubs in `tests/Stubs/CmsFrameworkAdminWidgets/` autoloaded into the real cms-framework namespace via `autoload-dev`, so the bridge is exercised without pulling in the framework's transitive deps. Includes `Livewire::test()` mounts of the wrapper classes to catch hydration-breaking regressions in the subclasses themselves.
- **Comprehensive documentation** under `/docs` — installation, components (Livewire / React / Vue / custom), HTTP endpoints, reporting API, scopes, CMS framework bridge, API reference, testing patterns, FAQ, and troubleshooting.

### Changed

- **`TestCase`** binds `AdminWidgetManager` as a singleton in `defineEnvironment()` so boot-time widget registrations are visible to tests without re-booting the service provider. Fixes a class of test-quality regressions where non-idempotent boot could ship green.

## [1.0.0] - (pending)

Initial release. Complete list of scoped features:

### Added

- **`SearchAnalyticsClient`** — typed wrapper over Google's `searchAnalytics.query` endpoint, with per-connection-per-query cache keys, config-driven timeout (guards against Guzzle's `timeout(0)` = "no timeout" footgun), and well-typed exceptions (`BaseNotInstalledException`, `ReportingException`).
- **Three fetchers**:
  - `PerformanceOverviewFetcher` — two queries (totals + daily trend), `hasData` distinguishes "empty" from "all zeros" so fresh properties don't render misleading zero-metric cards.
  - `TopQueriesFetcher` — top queries by clicks. `DEFAULT_LIMIT = 50`.
  - `TopPagesFetcher` — top pages by clicks. `DEFAULT_LIMIT = 50`.
- **Typed value objects** — `DateRange`, `SearchAnalyticsRequest`, `SearchAnalyticsResponse`, `PerformanceOverviewData`, `TopQueriesData`, `TopPagesData`. Every DTO exposes a `toArray()` matching the HTTP endpoints' JSON shape.
- **Three Livewire components** — `PerformanceCard`, `TopQueriesTable`, `TopPagesTable`. Auto-registered under `google-search-console::*` aliases when Livewire is installed. Handle missing-base state, unconnected state, empty state, and API errors uniformly.
- **Three React components** — `PerformanceCard`, `TopQueriesTable`, `TopPagesTable`. Ship as source under `resources/js/react/`. Use a shared TypeScript fetch client (`resources/js/shared/`) so both React and Vue talk to the same server payload shape.
- **Three Vue 3 components** — mirroring React's API. `resources/js/vue/`.
- **Three JSON HTTP endpoints** backing the React and Vue components: `GET /performance`, `GET /top-queries`, `GET /top-pages`. Mounted under a configurable prefix (default `/google-search-console`) with `web` + `auth` middleware. Deliberately ignore any user-supplied `?site_url` override so tenants can't cross-query.
- **OAuth scope contribution** — `webmasters.readonly` is contributed to the base `artisanpack-ui/google` package's shared `ScopeRegistry` via the `ap.google.scopes` filter hook. Single consent screen covers Search Console alongside every other Google service.
- **Cache layer** — `SearchAnalyticsClient` caches successful responses via Laravel's default cache store (`google-search-console.reporting.cache_ttl` seconds, default `300`). Per-connection, per-query cache keys prevent cross-tenant contamination.
- **Publishable assets**: `google-search-console-config`, `google-search-console-views`, `google-search-console-js`.
- **Support helpers**:
  - `BaseInstalled` — memoised detector for `artisanpack-ui/google`.
  - `GoogleConnectionResolver` — user → connected `GoogleConnection` mapping. Rebindable per tenant.
- **Laravel 10, 11, 12, and 13 support** via widened `illuminate/support` constraint.

### Security

- Controllers ignore any user-supplied `?site_url` query string. The site URL comes from config only — accepting a caller-supplied override would let one authenticated user query any property the shared Google account owns.
- Cache keys hash the connection ID + query payload so distinct users cannot see each other's cached rows.
- The `SearchAnalyticsClient` timeout falls back to 30 seconds when the config value is `null` / `0` / non-numeric — a stray unset env var can't hang the worker on a stuck endpoint.

### Notes

- No new database migrations. All persisted data (`google_connections` tokens) lives in the base package's tables.
- No secrets stored by this package. `GSC_SITE_URL` is public information.
