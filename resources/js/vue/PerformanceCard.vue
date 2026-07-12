<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import {
    fetchGscPerformance,
    GscPerformanceError,
    type FetchPerformanceOptions,
    type GscPerformanceData,
} from '../shared/performance'

const props = withDefaults(
    defineProps<{
        initialDays?: number
        siteUrl?: string | null
        baseUrl?: string
        fetchImpl?: FetchPerformanceOptions['fetchImpl']
    }>(),
    { initialDays: 28, siteUrl: null },
)

const RANGE_OPTIONS = [7, 28, 90, 180]

const days = ref<number>(props.initialDays)
const data = ref<GscPerformanceData | null>(null)
const errorMessage = ref<string | null>(null)
const errorCode = ref<string | null>(null)
const loading = ref<boolean>(false)

let activeController: AbortController | null = null

async function load(nextDays: number) {
    activeController?.abort()
    const controller = new AbortController()
    activeController = controller

    loading.value = true
    errorMessage.value = null
    errorCode.value = null

    try {
        const result = await fetchGscPerformance({
            days: nextDays,
            siteUrl: props.siteUrl,
            baseUrl: props.baseUrl,
            fetchImpl: props.fetchImpl,
            signal: controller.signal,
        })
        if (controller.signal.aborted) return
        data.value = result
    } catch (e) {
        if (controller.signal.aborted) return
        if (e instanceof GscPerformanceError) {
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

function formatInt(value: number | undefined): string {
    return new Intl.NumberFormat().format(Math.round(value ?? 0))
}
function formatPercent(value: number | undefined): string {
    return ((value ?? 0) * 100).toFixed(2)
}
function formatDecimal(value: number | undefined, digits: number): string {
    return (value ?? 0).toFixed(digits)
}
function maxClicks(): number {
    return data.value?.trend.reduce((max, row) => Math.max(max, row.clicks), 0) || 1
}
</script>

<template>
    <div v-if="errorCode === 'base_not_installed'" class="ap-gsc-performance__missing-base" role="alert">
        <p>{{ errorMessage }}</p>
        <pre><code>composer require artisanpack-ui/google</code></pre>
    </div>

    <div v-else class="ap-gsc-performance">
        <header class="ap-gsc-performance__header">
            <h2 class="ap-gsc-performance__title">Search performance</h2>
            <label class="ap-gsc-performance__range">
                <span class="ap-gsc-performance__range-label">Range</span>
                <select v-model.number="days" :disabled="loading">
                    <option v-for="option in RANGE_OPTIONS" :key="option" :value="option">
                        Last {{ option }} days
                    </option>
                </select>
            </label>
        </header>

        <div v-if="errorMessage" class="ap-gsc-performance__error" role="alert">
            {{ errorMessage }}
        </div>

        <template v-else>
            <dl class="ap-gsc-performance__totals">
                <div class="ap-gsc-performance__tile">
                    <dt>Clicks</dt>
                    <dd>{{ formatInt(data?.totals.clicks) }}</dd>
                </div>
                <div class="ap-gsc-performance__tile">
                    <dt>Impressions</dt>
                    <dd>{{ formatInt(data?.totals.impressions) }}</dd>
                </div>
                <div class="ap-gsc-performance__tile">
                    <dt>Avg. CTR</dt>
                    <dd>{{ formatPercent(data?.totals.ctr) }}%</dd>
                </div>
                <div class="ap-gsc-performance__tile">
                    <dt>Avg. position</dt>
                    <dd>{{ formatDecimal(data?.totals.position, 1) }}</dd>
                </div>
            </dl>

            <figure class="ap-gsc-performance__chart" aria-label="Daily clicks and impressions trend">
                <ol v-if="data && data.trend.length > 0" class="ap-gsc-performance__bars">
                    <li
                        v-for="row in data.trend"
                        :key="row.date"
                        class="ap-gsc-performance__bar"
                        :style="{ '--ap-bar-height': `${Math.min(100, Math.round((row.clicks / maxClicks()) * 100))}%` }"
                        :title="`${row.date}: ${formatInt(row.clicks)} clicks`"
                    >
                        <span class="visually-hidden">
                            {{ row.date }} — {{ formatInt(row.clicks) }} clicks
                        </span>
                    </li>
                </ol>
                <p v-else class="ap-gsc-performance__empty">No data for this range yet.</p>
            </figure>
        </template>
    </div>
</template>
