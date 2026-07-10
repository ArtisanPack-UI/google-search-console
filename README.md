# ArtisanPack UI Google Search Console

Search Console performance data and UI components for the ArtisanPack UI ecosystem. Uses the shared [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) base package for OAuth2 authentication, token storage/refresh, and scope management, and calls the Search Console API to surface performance and coverage insights.

## Installation

Install the package via Composer:

```bash
composer require artisanpack-ui/google-search-console
```

The service provider and `GoogleSearchConsole` facade are auto-discovered by Laravel. The base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package is pulled in automatically.

## Usage

Resolve the GoogleSearchConsole service from the container using the `googleSearchConsole()` helper or the `GoogleSearchConsole` facade:

```php
use ArtisanPackUI\GoogleSearchConsole\Facades\GoogleSearchConsole;

$searchConsole = googleSearchConsole();
// or
$searchConsole = GoogleSearchConsole::getFacadeRoot();
```

Property selection, performance reporting, and Livewire UI components will be documented here as they ship.

## Contributing

Please [read through the contributing guidelines](CONTRIBUTING.md) to learn more about how you can contribute to this project.
