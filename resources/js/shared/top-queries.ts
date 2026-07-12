/**
 * Shared client for the Search Console top-queries HTTP endpoint.
 */

export interface GscTopQueryRow {
    query: string
    clicks: number
    impressions: number
    ctr: number
    position: number
}

export interface GscTopQueriesData {
    range: { startDate: string; endDate: string }
    rows: GscTopQueryRow[]
}

export interface FetchTopQueriesOptions {
    baseUrl?: string
    days?: number
    limit?: number
    siteUrl?: string | null
    fetchImpl?: typeof fetch
    signal?: AbortSignal
}

export class GscTopQueriesError extends Error {
    public readonly code: string
    public readonly status: number
    constructor(code: string, status: number, message: string) {
        super(message)
        this.name = 'GscTopQueriesError'
        this.code = code
        this.status = status
    }
}

export async function fetchGscTopQueries(
    options: FetchTopQueriesOptions = {},
): Promise<GscTopQueriesData> {
    const {
        baseUrl = '/google-search-console/top-queries',
        days = 28,
        limit = 25,
        siteUrl = null,
        fetchImpl = typeof fetch !== 'undefined' ? fetch : undefined,
        signal,
    } = options

    if (!fetchImpl) {
        throw new GscTopQueriesError(
            'no_fetch',
            0,
            'A global fetch implementation is not available. Pass options.fetchImpl explicitly.',
        )
    }

    const params = new URLSearchParams({ days: String(days), limit: String(limit) })
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
        throw new GscTopQueriesError(code, response.status, message)
    }

    return (await response.json()) as GscTopQueriesData
}
