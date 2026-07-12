---
title: Components
---

# Components

The package ships three UI implementations — one Livewire, one React, one Vue — of each of the three reports it exposes. All three implementations render the same information and are backed by the same server-side data:

| Report | Livewire alias | React file | Vue file |
|---|---|---|---|
| Performance card (clicks, impressions, CTR, position + trend) | `google-search-console::performance-card` | `resources/js/react/PerformanceCard.tsx` | `resources/js/vue/PerformanceCard.vue` |
| Top queries table | `google-search-console::top-queries-table` | `resources/js/react/TopQueriesTable.tsx` | `resources/js/vue/TopQueriesTable.vue` |
| Top pages table | `google-search-console::top-pages-table` | `resources/js/react/TopPagesTable.tsx` | `resources/js/vue/TopPagesTable.vue` |

Every component handles:

- **Missing base package** — renders an install-the-base call to action.
- **No connected Google account** — renders a connect call to action.
- **Empty data** — renders an empty state rather than a misleading zero-metric card.
- **API errors** — renders the message, not a stack trace.

Sub-pages:

- [[Components/Livewire|Livewire components]] — the three `<livewire:google-search-console::*>` components.
- [[Components/React|React components]] — three React components + the shared TypeScript fetch client.
- [[Components/Vue|Vue components]] — three Vue components + the same shared client.
- [[Components/Custom|Building your own]] — fetching directly from the [[HTTP Endpoints|HTTP endpoints]] to render in any framework.

## Data flow

The Livewire components fetch server-side inside `mount()`:

```
Livewire::mount() → SearchAnalyticsClient::query() → Google API
                                                   ↓
                                            component state
                                                   ↓
                                                render
```

The React and Vue components fetch client-side via `fetch()` against the [[HTTP Endpoints|package's HTTP endpoints]]:

```
React/Vue::mount() → fetch('/google-search-console/performance')
                          ↓
                    controller → SearchAnalyticsClient::query() → Google API
                          ↓
                     JSON response
                          ↓
                    component state
                          ↓
                       render
```

The shape a component ends up rendering is identical either way — the controllers serialise the same DTOs the Livewire components put on `$this->totals`, `$this->trend`, `$this->rows`.

## Choosing a component

| Framework | Ship with | Best fit |
|---|---|---|
| [[Components/Livewire|Livewire]] | Package (auto-registered when Livewire is installed) | Blade-first apps. Server-side rendering, no bundler config needed. |
| [[Components/React|React]] | Package (`resources/js/react/`) | React SPAs and hybrid Blade + Inertia + React apps. |
| [[Components/Vue|Vue]] | Package (`resources/js/vue/`) | Vue SPAs and hybrid Blade + Inertia + Vue apps. |
| [[Components/Custom|Custom]] | Your code | Any other framework — call the [[HTTP Endpoints|HTTP endpoints]] yourself. |

The Livewire components render server-side and can be dropped into any Blade view without touching a bundler. The React and Vue components need to be imported through your bundler (or published into your app's tree via `vendor:publish --tag=google-search-console-js`).

## Labels and i18n

The Livewire surfaces run every string through `__()` / `trans_choice()`. The React and Vue components can't call those directly, so they inline English defaults — override by publishing the JS sources and editing them, or by wrapping the components in your own framework's i18n layer before rendering.

---
Continue to [[HTTP Endpoints]] →
