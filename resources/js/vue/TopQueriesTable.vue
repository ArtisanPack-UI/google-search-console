<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import {
    fetchGscTopQueries,
    GscTopQueriesError,
    type FetchTopQueriesOptions,
    type GscTopQueriesData,
    type GscTopQueryRow,
} from '../shared/top-queries'

const props = withDefaults(
    defineProps<{
        initialDays?: number
        limit?: number
        perPage?: number
        siteUrl?: string | null
        baseUrl?: string
        fetchImpl?: FetchTopQueriesOptions['fetchImpl']
    }>(),
    { initialDays: 28, limit: 25, perPage: 10, siteUrl: null },
)

type SortColumn = keyof GscTopQueryRow
type SortDir = 'asc' | 'desc'

const RANGE_OPTIONS = [7, 28, 90, 180]

const COLUMNS: Array<{ key: SortColumn; label: string; defaultDir: SortDir }> = [
    { key: 'query', label: 'Query', defaultDir: 'asc' },
    { key: 'clicks', label: 'Clicks', defaultDir: 'desc' },
    { key: 'impressions', label: 'Impressions', defaultDir: 'desc' },
    { key: 'ctr', label: 'CTR', defaultDir: 'desc' },
    { key: 'position', label: 'Position', defaultDir: 'asc' },
]

const days = ref<number>(props.initialDays)
const data = ref<GscTopQueriesData | null>(null)
const errorMessage = ref<string | null>(null)
const errorCode = ref<string | null>(null)
const loading = ref<boolean>(false)
const sortBy = ref<SortColumn>('clicks')
const sortDir = ref<SortDir>('desc')
const page = ref<number>(1)

let activeController: AbortController | null = null

async function load(nextDays: number) {
    activeController?.abort()
    const controller = new AbortController()
    activeController = controller

    loading.value = true
    errorMessage.value = null
    errorCode.value = null

    try {
        const result = await fetchGscTopQueries({
            days: nextDays,
            limit: props.limit,
            siteUrl: props.siteUrl,
            baseUrl: props.baseUrl,
            fetchImpl: props.fetchImpl,
            signal: controller.signal,
        })
        if (controller.signal.aborted) return
        data.value = result
        page.value = 1
    } catch (e) {
        if (controller.signal.aborted) return
        if (e instanceof GscTopQueriesError) {
            errorCode.value = e.code
            errorMessage.value = e.message
        } else if (e instanceof Error) {
            errorMessage.value = e.message
        }
    } finally {
        if (!controller.signal.aborted) {
            loading.value = false
        }
    }
}

onMounted(() => load(days.value))
onUnmounted(() => activeController?.abort())
watch(days, (next) => load(next))

const sortedRows = computed<GscTopQueryRow[]>(() => {
    if (!data.value) return []
    const rows = [...data.value.rows]
    const dir = sortDir.value === 'asc' ? 1 : -1
    const key = sortBy.value
    rows.sort((a, b) => {
        const left = a[key]
        const right = b[key]
        if (typeof left === 'number' && typeof right === 'number') {
            return (left - right) * dir
        }
        return String(left).localeCompare(String(right)) * dir
    })
    return rows
})

const totalPages = computed<number>(() =>
    Math.max(1, Math.ceil(sortedRows.value.length / props.perPage)),
)
const visibleRows = computed<GscTopQueryRow[]>(() =>
    sortedRows.value.slice((page.value - 1) * props.perPage, page.value * props.perPage),
)

function handleSort(column: SortColumn) {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        const columnDef = COLUMNS.find((c) => c.key === column)
        sortBy.value = column
        sortDir.value = columnDef?.defaultDir ?? 'desc'
    }
    page.value = 1
}

function formatInt(value: number): string {
    return new Intl.NumberFormat().format(Math.round(value))
}
function formatPercent(value: number): string {
    return (value * 100).toFixed(2)
}
</script>

<template>
    <div v-if="errorCode === 'base_not_installed'" class="ap-gsc-top-queries__missing-base" role="alert">
        <p>{{ errorMessage }}</p>
        <pre><code>composer require artisanpack-ui/google</code></pre>
    </div>

    <div v-else class="ap-gsc-top-queries">
        <header class="ap-gsc-top-queries__header">
            <h2 class="ap-gsc-top-queries__title">Top queries</h2>
            <label class="ap-gsc-top-queries__range">
                <span class="ap-gsc-top-queries__range-label">Range</span>
                <select v-model.number="days" :disabled="loading">
                    <option v-for="option in RANGE_OPTIONS" :key="option" :value="option">
                        Last {{ option }} days
                    </option>
                </select>
            </label>
        </header>

        <div v-if="errorMessage" class="ap-gsc-top-queries__error" role="alert">
            {{ errorMessage }}
        </div>

        <template v-else-if="visibleRows.length > 0">
            <table class="ap-gsc-top-queries__table">
                <thead>
                    <tr>
                        <th v-for="col in COLUMNS" :key="col.key" scope="col">
                            <button
                                type="button"
                                class="ap-gsc-top-queries__sort"
                                @click="handleSort(col.key)"
                                :aria-sort="sortBy === col.key ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'"
                            >
                                {{ col.label }}
                                <span v-if="sortBy === col.key" aria-hidden="true">{{ sortDir === 'asc' ? '▲' : '▼' }}</span>
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in visibleRows" :key="`${index}:${row.query}`">
                        <th scope="row">{{ row.query }}</th>
                        <td>{{ formatInt(row.clicks) }}</td>
                        <td>{{ formatInt(row.impressions) }}</td>
                        <td>{{ formatPercent(row.ctr) }}%</td>
                        <td>{{ row.position.toFixed(1) }}</td>
                    </tr>
                </tbody>
            </table>

            <nav class="ap-gsc-top-queries__pagination" aria-label="Top queries pagination">
                <button type="button" @click="page = Math.max(1, page - 1)" :disabled="page <= 1">Previous</button>
                <span>Page {{ page }} of {{ totalPages }}</span>
                <button
                    type="button"
                    @click="page = Math.min(totalPages, page + 1)"
                    :disabled="page >= totalPages"
                >
                    Next
                </button>
            </nav>
        </template>

        <p v-else class="ap-gsc-top-queries__empty">No queries for this range yet.</p>
    </div>
</template>
