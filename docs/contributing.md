---
title: Contributing
---

# Contributing

The authoritative contribution guide lives at `CONTRIBUTING.md` in the package root. This page summarizes what a code contributor to `artisanpack-ui/google-search-console` specifically needs to know.

## Development setup

Fork and clone the package repository, then pull it into an ArtisanPack UI dev app via a Composer path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../artisanpack-ui-google-search-console",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "artisanpack-ui/google-search-console": "@dev"
    }
}
```

The `artisanpack-ui-dev` app in this repository is already wired up this way — symlinks live under `packages/google-search-console/`.

Install package dependencies:

```bash
cd packages/google-search-console
composer install
```

## Prerequisites for full local runs

The reporting side of the package needs `artisanpack-ui/google` installed and a connected Google account. For UI-only work (styling, view tweaks, prop wiring):

- Set `GSC_FAKE_DATA=true` in the dev app's `.env` — the dev app's `/gsc-test` surface stubs the resolver and client with synthetic data, so you don't need a real Google connection or a verified property.

For actual API testing:

- Configure `artisanpack-ui/google` per its own docs.
- Set `GSC_SITE_URL` to a property this account owns.
- Verify with the `/gsc-test/sites-list` debug helper.

## Running tests

```bash
composer test          # runs Pest
```

Filter to a single file or test:

```bash
./vendor/bin/pest tests/Feature/SearchAnalyticsClientTest.php
./vendor/bin/pest --filter="caches successful query"
```

The test suite uses Orchestra Testbench with an in-memory SQLite database. See [[Testing]] for the reference patterns.

## Code style

```bash
composer lint          # php-cs-fixer --dry-run + phpcs
composer fix           # php-cs-fixer fix
composer cs            # phpcs only
composer cs:fix        # phpcbf (auto-fix what phpcs can)
```

The package follows the ArtisanPack UI code style — WordPress-style spacing (`if ( $condition )`, `[ 'key' => 'value' ]`), Yoda conditions, aligned operators, trailing commas in multiline. `composer fix` handles ~70% of it; `composer cs` catches the rest.

Configuration:

- `.php-cs-fixer.dist.php` — PHP-CS-Fixer rules, including custom `spaces_inside_parenthesis` / `spaces_inside_brackets` fixers.
- `phpcs.xml` — PHPCS rules for what PHP-CS-Fixer can't enforce (disallowed functions, PHP tag placement).

Run both before opening a merge request.

## Branch strategy

- `main` — release-ready code. Never commit directly.
- `feature/*`, `fix/*`, `chore/*`, `docs/*` — branch prefixes for the type of change.
- Release branches: `release/x.y.z`.

Merge requests target `main` (or the active release branch during a pre-release cycle). Squash-merge is the default so `main` stays linear.

## Commit messages

Conventional commits, matching the ArtisanPack UI convention:

```
feat: add SearchAnalyticsClient caching
fix: preserve refresh_token on incremental consent
docs: clarify GSC_SITE_URL format
chore: bump orchestra/testbench to ^10.2
test: cover CMS bridge widget registration
refactor: extract widget type map to static method
```

Types: `feat`, `fix`, `docs`, `chore`, `refactor`, `test`, `ci`.

## PRs must include

1. **A test** — every behavior change gets a Pest test (unit or feature). Bug fixes get a regression test.
2. **Docs updates** — if you're touching public API, changing config, or shifting behavior in a way callers can observe, update the corresponding page under `/docs`.
3. **Changelog entry** — add a line to `CHANGELOG.md` under `## Unreleased`.
4. **Passing CI** — `composer lint` and `composer test` must be green.

## What to work on

Good first issues:

- Adding more `dimensionFilterGroups` operator coverage in the docs (with worked examples).
- A fetcher for one of the dimensions the shipped three don't cover (`country`, `device`, `searchAppearance`).
- Additional adversarial tests for the client's cache-key stability.

Bigger scoped work:

- URL Inspection API client (probably in a new package that reuses `SearchAnalyticsClient`'s auth pattern).
- Bulk export / job pattern that paginates past `MAX_ROW_LIMIT` for large datasets.
- First-class multi-tenant helpers instead of the manual `SearchAnalyticsClient` rebind pattern.

## Getting help

- GitHub issues on the [`artisanpack-ui/google-search-console`](https://github.com/ArtisanPack-UI/google-search-console) repo.
- The maintainer email address is in `composer.json`.

## Code of conduct

See the top-level `CONTRIBUTING.md` for the code of conduct that covers every ArtisanPack UI project.
