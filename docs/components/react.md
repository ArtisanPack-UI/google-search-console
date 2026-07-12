---
title: React Components
---

# React Components

Three React components ship as source under `resources/js/react/`. They fetch client-side from the [HTTP endpoints](HTTP-Endpoints) and render the same reports as the Livewire and Vue equivalents.

## Requirements

- React 18+ (uses `useEffect`, `useState`, `useCallback`).
- A bundler that compiles TSX (Vite, Webpack, Turbopack, …).
- An authenticated session — the endpoints run under `web` + `auth` middleware, so the browser must be sending the app's session cookie with the fetch.

## Getting the sources

Import directly from the package (if your bundler can resolve into `vendor/`):

```tsx
import { PerformanceCard } from '@artisanpack-ui/google-search-console/react/PerformanceCard'
import { TopQueriesTable } from '@artisanpack-ui/google-search-console/react/TopQueriesTable'
import { TopPagesTable }   from '@artisanpack-ui/google-search-console/react/TopPagesTable'
```

Or publish the sources into your app tree:

```bash
php artisan vendor:publish --tag=google-search-console-js
```

Copies `resources/js/react/`, `resources/js/vue/`, and `resources/js/shared/` under `resources/js/vendor/google-search-console/`. From there:

```tsx
import { PerformanceCard } from '@/vendor/google-search-console/react/PerformanceCard'
```

## `<PerformanceCard />`

Four-metric summary + daily trend.

```tsx
<PerformanceCard initialDays={28} />
```

### Props

| Prop | Type | Default | Notes |
|---|---|---|---|
| `initialDays` | `number` | `28` | Starting range. The component's built-in range dropdown lets the user switch to 7 / 28 / 90 / 180. |
| `siteUrl` | `string \| null` | `null` | Override the configured site. **Ignored by the server** — the controller reads config only. Useful only if you're pointing `baseUrl` at your own endpoint. |
| `baseUrl` | `string` | `/google-search-console` | Prefix for the JSON endpoint. Change if your app moved the routes via `google-search-console.routes.prefix`. |
| `fetchImpl` | `typeof fetch` | `window.fetch` | Injectable fetch — swap for SSR or testing. |

### State transitions

The component internally tracks:

- `data: GscPerformanceData | null` — populated on first successful fetch.
- `loading: boolean` — mid-flight indicator.
- `errorCode: string | null` — one of `unauthenticated`, `not_connected`, `base_not_installed`, `reporting_error`, or `null`.
- `errorMessage: string | null` — server-provided message for the current error.

Renders one of:

- **Missing base** (`errorCode === 'base_not_installed'`) — install-the-base CTA.
- **Not connected** (`errorCode === 'not_connected'`) — connect-a-Google-account CTA.
- **Unauthenticated** (`errorCode === 'unauthenticated'`) — sign-in CTA.
- **Reporting error** (`errorCode === 'reporting_error'`) — the message from Google.
- **No data** — `data.hasData === false`.
- **Success** — totals tiles + daily-clicks bar trend.

## `<TopQueriesTable />`

Sortable, paginated table of top queries.

```tsx
<TopQueriesTable initialDays={28} initialLimit={50} />
```

### Props

| Prop | Type | Default | Notes |
|---|---|---|---|
| `initialDays` | `number` | `28` | Starting range. |
| `initialLimit` | `number` | `50` | Number of rows to pull from Google in one request. Clamped server-side to `1..1000`. |
| `siteUrl` | `string \| null` | `null` | (See caveat under PerformanceCard.) |
| `baseUrl` | `string` | `/google-search-console` | |
| `fetchImpl` | `typeof fetch` | `window.fetch` | |

## `<TopPagesTable />`

Same API as `TopQueriesTable`, but rows are keyed by page URL instead of query text.

```tsx
<TopPagesTable initialDays={28} initialLimit={50} />
```

## The shared fetch client

Under the hood every component calls one of three functions in `resources/js/shared/`:

- `fetchGscPerformance(options): Promise<GscPerformanceData>`
- `fetchGscTopQueries(options): Promise<GscTopQueriesData>`
- `fetchGscTopPages(options): Promise<GscTopPagesData>`

They handle URL construction, JSON parsing, and wrap every non-2xx response in a typed error class (`GscPerformanceError`, etc.) with a `code` matching the server's `errorCode`.

Call them directly if you want your own UI:

```tsx
import { fetchGscPerformance, GscPerformanceError } from '@artisanpack-ui/google-search-console/shared/performance'

async function load() {
    try {
        const data = await fetchGscPerformance({ days: 28 })
        // data.totals, data.trend, data.hasData, data.range
    } catch ( err ) {
        if ( err instanceof GscPerformanceError ) {
            // err.code, err.message
        }
    }
}
```

See [Components/Custom](Components-Custom) for the full API.

## Aborting in-flight requests

Every component wraps its fetches in an `AbortController` that's aborted on unmount and when a new range is selected — no zombie requests, no state-after-unmount warnings. If you're calling `fetchGscPerformance` directly, pass a `signal`:

```tsx
const controller = new AbortController()
fetchGscPerformance({ days: 28, signal: controller.signal })
// later:
controller.abort()
```

## Styling

The components emit BEM-classed markup (`ap-gsc-performance__tile`, `ap-gsc-top-queries__sort`, …) with no styles applied. Bring your own CSS. If you're on Tailwind, wrap the component in a container and use Tailwind's `[&_.ap-gsc-performance__tile]:...` variant syntax to style descendants without touching the source.

## Testing

Use React Testing Library with a fake fetch:

```tsx
import { render, screen } from '@testing-library/react'
import { PerformanceCard } from '@artisanpack-ui/google-search-console/react/PerformanceCard'

const fakeFetch = async ( input: RequestInfo | URL ) => new Response(
    JSON.stringify( {
        range: { startDate: '2026-01-01', endDate: '2026-01-28' },
        totals: { clicks: 42, impressions: 500, ctr: 0.084, position: 3.2 },
        trend: [],
        hasData: true,
    } ),
    { status: 200, headers: { 'Content-Type': 'application/json' } },
)

test( 'renders totals from fake fetch', async () => {
    render( <PerformanceCard fetchImpl={fakeFetch as typeof fetch} /> )
    expect( await screen.findByText( '42' ) ).toBeInTheDocument()
} )
```

The `fetchImpl` prop is deliberately typed so the injected function can differ from real `fetch` — the components only call `fetchImpl(url)`.

## Publishing sources into your app

```bash
php artisan vendor:publish --tag=google-search-console-js
```

Copies:

- `resources/js/vendor/google-search-console/react/` — three `.tsx` files.
- `resources/js/vendor/google-search-console/vue/` — three `.vue` files.
- `resources/js/vendor/google-search-console/shared/` — the fetch client.

From there you can edit freely. `git`-ignore the vendor path or check it in, depending on your policy.
