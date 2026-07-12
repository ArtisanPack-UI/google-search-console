# ArtisanPack UI GoogleSearchConsole Changelog

## Unreleased

### Added

- Optional CMS framework bridge — three widget wrappers (`PerformanceCardWidget`, `TopQueriesTableWidget`, `TopPagesTableWidget`) that register with `artisanpack-ui/cms-framework`'s `AdminWidgetManager` when the framework is installed. Bridge is dormant when either the framework or Livewire is absent.
- `CmsFrameworkInstalled` support class — presence detector mirroring `BaseInstalled`.
- Rendering test suite covering mount, empty state, sort order, pagination, and missing-base state for all three Livewire components.
- CMS bridge test suite exercising the wrappers, metadata, boot-time registration, and the framework-absent guard. Uses local stubs autoloaded into the real cms-framework namespace so the bridge is testable without pulling the framework's transitive deps into `require-dev`.
- Comprehensive `/docs` tree — installation, components (Livewire / React / Vue / custom), HTTP endpoints, reporting, scopes, CMS bridge, API reference, testing, FAQ, troubleshooting.
- `GoogleSearchConsoleServiceProvider::cmsFrameworkWidgetTypeMap()` — public static widget-type map for testability.

### Changed

- `tests/TestCase` binds `AdminWidgetManager` as a singleton in `defineEnvironment()` so boot-time widget registrations are visible to tests without re-booting the service provider.

## 1.0.0

- Initial scaffold from the ArtisanPack UI package blueprint.
- Depends on `artisanpack-ui/google` for shared OAuth2, token, and scope management.
- Adds `illuminate/support` support for Laravel 10, 11, 12, and 13.
- Server-side `SearchAnalyticsClient` wrapping Google's `searchAnalytics.query` endpoint, with per-connection cache keys and typed exceptions.
- Three high-level fetchers (`PerformanceOverviewFetcher`, `TopQueriesFetcher`, `TopPagesFetcher`) returning typed DTOs.
- Three Livewire components (`google-search-console::performance-card`, `top-queries-table`, `top-pages-table`), auto-registered when Livewire is installed.
- Matching React and Vue components under `resources/js/`.
- Three JSON HTTP endpoints (`GET /performance`, `/top-queries`, `/top-pages`) mounted under a configurable prefix with `web` + `auth` middleware.
- Contributes `webmasters.readonly` to the base package's shared `ScopeRegistry` via the `ap.google.scopes` filter hook.
- Publishable config, views, and JS asset tags.
