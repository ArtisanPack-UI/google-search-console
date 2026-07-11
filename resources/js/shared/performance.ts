/**
 * Shared client for the Search Console performance HTTP endpoint.
 * Consumed by the React and Vue components so both frameworks talk to
 * the exact shape the server serialises.
 */

export interface GscPerformanceTotals {
    clicks: number
    impressions: number
    ctr: number
    position: number
}

export interface GscPerformanceTrendPoint {
    date: string
    clicks: number
    impressions: number
    ctr: number
    position: number
}

export interface GscPerformanceData {
    range: { startDate: string; endDate: string }
    totals: GscPerformanceTotals
    trend: GscPerformanceTrendPoint[]
}

export interface FetchPerformanceOptions {
    /** Base URL for the performance endpoint. Defaults to `/google-search-console/performance`. */
    baseUrl?: string
    /** Number of days to include in the range. Defaults to 28. */
    days?: number
    /** Optional site URL override. Uses the server-configured site when omitted. */
    siteUrl?: string | null
    /** Optional fetch instance override, useful for SSR / testing. */
    fetchImpl?: typeof fetch
    /** Optional AbortSignal to cancel the request. */
    signal?: AbortSignal
}

export class GscPerformanceError extends Error {
    public readonly code: string
    public readonly status: number
    constructor(code: string, status: number, message: string) {
        super(message)
        this.name = 'GscPerformanceError'
        this.code = code
        this.status = status
    }
}

/**
 * Fetch the performance payload from the server.
 *
 * Throws {@link GscPerformanceError} on any non-2xx response so
 * consumers can display code-driven UI (e.g. "connect a Google
 * account") rather than parsing raw JSON.
 */
export async function fetchGscPerformance(
    options: FetchPerformanceOptions = {},
): Promise<GscPerformanceData> {
    const {
        baseUrl = '/google-search-console/performance',
        days = 28,
        siteUrl = null,
        fetchImpl = typeof fetch !== 'undefined' ? fetch : undefined,
        signal,
    } = options

    if (!fetchImpl) {
        throw new GscPerformanceError(
            'no_fetch',
            0,
            'A global fetch implementation is not available. Pass options.fetchImpl explicitly.',
        )
    }

    const params = new URLSearchParams({ days: String(days) })
    if (siteUrl) {
        params.set('site_url', siteUrl)
    }

    const url = `${baseUrl}?${params.toString()}`
    const response = await fetchImpl(url, {
        headers: { Accept: 'application/json' },
        signal,
        credentials: 'same-origin',
    })

    if (!response.ok) {
        let code = 'http_error'
        let message = `Request failed with status ${response.status}`
        try {
            const body = (await response.json()) as { error?: string; message?: string }
            if (body?.error) code = body.error
            if (body?.message) message = body.message
        } catch {
            /* body wasn't JSON — keep the defaults */
        }
        throw new GscPerformanceError(code, response.status, message)
    }

    return (await response.json()) as GscPerformanceData
}
