---
title: Home
---

# ArtisanPack UI Google Search Console Documentation

Welcome to the documentation for **ArtisanPack UI Google Search Console** — a Laravel package that surfaces Google Search Console performance data via a typed API client, an HTTP JSON layer, and drop-in Livewire, React, and Vue components.

The package sits on top of [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) for OAuth2 authentication, token storage/refresh, and single-consent scope management. Every Search Console API call authenticates through the base package's token manager, and the `webmasters.readonly` scope is contributed to the base package's shared registry so it appears on the single-consent screen alongside every other Google service your app uses.

Links use the GitLab wiki page style, so you can jump between pages like [[Getting Started]] or [[Components]].

- [[Getting Started]]
- [[Installation]]
- [[Components]]
- [[HTTP Endpoints]]
- [[Reporting]]
- [[Scopes]]
- [[CMS Framework Bridge]]
- [[API Reference]]
- [[Testing]]
- [[FAQ]]
- [[Troubleshooting]]
- [[Contributing]]

Release history lives in [`CHANGELOG.md`](../CHANGELOG.md) at the repo root — the single source of truth.

If you're new here, start with [[Getting Started]].

## What this package does

- **Server-side Search Console client** — `SearchAnalyticsClient` wraps Google's `searchAnalytics.query` endpoint with typed requests, typed responses, a caching layer, and well-typed exceptions.
- **Three ready-made fetchers** — `PerformanceOverviewFetcher`, `TopQueriesFetcher`, and `TopPagesFetcher` build the exact queries the shipped UI needs and return typed DTOs (`PerformanceOverviewData`, `TopQueriesData`, `TopPagesData`).
- **Three interchangeable UI surfaces** — a Livewire component, a React component, and a Vue component for each of the three reports (performance card, top queries table, top pages table).
- **JSON HTTP endpoints** that back the React and Vue components. The Livewire component fetches server-side.
- **Optional CMS framework bridge** — three widget wrappers that register with `artisanpack-ui/cms-framework`'s `AdminWidgetManager` when the framework is installed, so the same UIs drop straight into the CMS admin dashboard.
- **OAuth scope contribution** via the base package's `ap.google.scopes` filter hook — the single-consent screen covers Search Console automatically.

## What this package does not do

- It does **not** run its own OAuth flow. Connecting the Google account is the base package's job — this package refuses to boot the reporting side if the base isn't installed.
- It does **not** manage which Search Console property to query. The verified `site_url` (URL property or Domain property) comes from config; multi-tenant apps override the `SearchAnalyticsClient` binding to scope by tenant.
- It does **not** call the URL Inspection API, the Sitemaps API, or perform site-verification. Only the searchAnalytics.query surface is wrapped.
- It does **not** persist Search Console data. Every request round-trips to Google unless a cached copy is still warm (`cache_ttl`, default 300s).

## Where the pieces live

```
packages/google-search-console/
├── src/
│   ├── GoogleSearchConsole.php              — the aggregator (facade + helper root)
│   ├── GoogleSearchConsoleServiceProvider.php
│   ├── Reporting/                           — client, request/response DTOs, fetchers
│   ├── Livewire/                            — 3 Livewire components
│   ├── Http/Controllers/                    — 3 controllers backing React/Vue
│   ├── Bridges/CmsFramework/AdminWidgets/   — 3 widget wrappers (optional bridge)
│   ├── Support/                             — BaseInstalled, CmsFrameworkInstalled, resolver
│   └── Exceptions/                          — BaseNotInstalledException, ReportingException
├── resources/
│   ├── views/livewire/                      — 3 Blade views
│   └── js/{react,vue,shared}/               — matching React/Vue components + shared fetch layer
├── routes/web.php                           — 3 JSON endpoints
├── config/google-search-console.php
├── docs/                                    — you are here
└── tests/
```
