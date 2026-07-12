---
title: Vue Components
---

# Vue Components

Three Vue 3 components ship as source under `resources/js/vue/`. They mirror the [React components](Components-React) API — same props, same states, same shared fetch client.

## Requirements

- Vue 3+ (uses the Composition API with `<script setup>`).
- A bundler that compiles Vue SFCs (Vite, Webpack + `vue-loader`, Turbopack, …).
- An authenticated session — the endpoints run under `web` + `auth` middleware, so the browser must send the app's session cookie with the fetch.

## Getting the sources

```vue
<script setup lang="ts">
import PerformanceCard from '@artisanpack-ui/google-search-console/vue/PerformanceCard.vue'
import TopQueriesTable from '@artisanpack-ui/google-search-console/vue/TopQueriesTable.vue'
import TopPagesTable   from '@artisanpack-ui/google-search-console/vue/TopPagesTable.vue'
</script>
```

Or publish the sources with:

```bash
php artisan vendor:publish --tag=google-search-console-js
```

Then import from `@/vendor/google-search-console/vue/…`.

## `<PerformanceCard />`

```vue
<template>
    <PerformanceCard :initial-days="28" />
</template>
```

### Props

| Prop | Type | Default | Notes |
|---|---|---|---|
| `initialDays` | `number` | `28` | Starting range. Built-in dropdown lets the user switch. |
| `siteUrl` | `string \| null` | `null` | Override the configured site — ignored by the server-side controller, but useful if you point `baseUrl` at your own endpoint. |
| `baseUrl` | `string` | `/google-search-console` | Prefix for the JSON endpoint. Change if you moved the routes via `google-search-console.routes.prefix`. |
| `fetchImpl` | `typeof fetch` | `window.fetch` | Injectable fetch — swap for SSR or testing. |

Emits no events. Everything is prop-in / render-out.

## `<TopQueriesTable />`

```vue
<TopQueriesTable :initial-days="28" :initial-limit="50" />
```

Same props as PerformanceCard plus `initialLimit: number` (default `50`, clamped server-side to `1..1000`).

## `<TopPagesTable />`

```vue
<TopPagesTable :initial-days="28" :initial-limit="50" />
```

Same API as `TopQueriesTable`, rows keyed by page URL instead of query text.

## State transitions

Every component tracks (using `ref`s):

- `data: GscPerformanceData | GscTopQueriesData | GscTopPagesData | null`
- `loading: boolean`
- `errorCode: string | null` — one of `unauthenticated`, `not_connected`, `base_not_installed`, `reporting_error`, or `null`.
- `errorMessage: string | null`

Rendered branches:

- **Missing base** — install-the-base CTA.
- **Not connected** — connect-a-Google-account CTA.
- **Unauthenticated** — sign-in CTA.
- **Reporting error** — the message from Google.
- **No data** — empty state.
- **Success** — the metrics / rows.

## Aborting in-flight requests

Each component owns an `AbortController` that's aborted `onUnmounted` and on every new range selection — no zombie fetches. If you're calling the shared fetch functions directly (`fetchGscPerformance` etc.), pass a `signal`.

## The shared fetch client

Identical to the React one — the `.tsx` and `.vue` files import from the same `resources/js/shared/` layer. See [Components/React](Components-React#the-shared-fetch-client) for the API.

Call it directly if you want your own UI:

```vue
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { fetchGscPerformance, GscPerformanceError } from '@artisanpack-ui/google-search-console/shared/performance'

const data = ref<Awaited<ReturnType<typeof fetchGscPerformance>> | null>( null )
const error = ref<string | null>( null )

onMounted( async () => {
    try {
        data.value = await fetchGscPerformance( { days: 28 } )
    } catch ( err ) {
        if ( err instanceof GscPerformanceError ) {
            error.value = err.message
        }
    }
} )
</script>

<template>
    <div v-if="error">{{ error }}</div>
    <div v-else-if="data">
        Clicks: {{ data.totals.clicks }}
    </div>
</template>
```

## Styling

Same story as React — BEM classes, no styles applied. Add your own scoped or global styles.

## Testing

With Vue Test Utils and a fake fetch:

```ts
import { mount } from '@vue/test-utils'
import PerformanceCard from '@artisanpack-ui/google-search-console/vue/PerformanceCard.vue'

const fakeFetch = async () => new Response(
    JSON.stringify( {
        range: { startDate: '2026-01-01', endDate: '2026-01-28' },
        totals: { clicks: 42, impressions: 500, ctr: 0.084, position: 3.2 },
        trend: [],
        hasData: true,
    } ),
    { status: 200, headers: { 'Content-Type': 'application/json' } },
)

test( 'renders totals from fake fetch', async () => {
    const wrapper = mount( PerformanceCard, { props: { fetchImpl: fakeFetch } } )
    await wrapper.vm.$nextTick()
    expect( wrapper.text() ).toContain( '42' )
} )
```

## Publishing sources into your app

```bash
php artisan vendor:publish --tag=google-search-console-js
```

Copies:

- `resources/js/vendor/google-search-console/react/` — three `.tsx` files.
- `resources/js/vendor/google-search-console/vue/` — three `.vue` files.
- `resources/js/vendor/google-search-console/shared/` — the fetch client.
