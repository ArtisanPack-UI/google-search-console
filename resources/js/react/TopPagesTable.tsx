import { useCallback, useEffect, useMemo, useState } from 'react'
import {
    fetchGscTopPages,
    GscTopPagesError,
    type FetchTopPagesOptions,
    type GscTopPageRow,
    type GscTopPagesData,
} from '../shared/top-pages'

export type { GscTopPagesData, GscTopPageRow }

export interface TopPagesTableProps {
    initialDays?: number
    limit?: number
    perPage?: number
    siteUrl?: string | null
    baseUrl?: string
    fetchImpl?: FetchTopPagesOptions['fetchImpl']
}

type SortColumn = keyof GscTopPageRow
type SortDir = 'asc' | 'desc'

const RANGE_OPTIONS = [7, 28, 90, 180]

const COLUMNS: Array<{ key: SortColumn; label: string; defaultDir: SortDir }> = [
    { key: 'page', label: 'Page', defaultDir: 'asc' },
    { key: 'clicks', label: 'Clicks', defaultDir: 'desc' },
    { key: 'impressions', label: 'Impressions', defaultDir: 'desc' },
    { key: 'ctr', label: 'CTR', defaultDir: 'desc' },
    { key: 'position', label: 'Position', defaultDir: 'asc' },
]

export function TopPagesTable(props: TopPagesTableProps): JSX.Element {
    const { initialDays = 28, limit = 25, perPage = 10, siteUrl, baseUrl, fetchImpl } = props

    const [days, setDays] = useState<number>(initialDays)
    const [data, setData] = useState<GscTopPagesData | null>(null)
    const [errorMessage, setErrorMessage] = useState<string | null>(null)
    const [errorCode, setErrorCode] = useState<string | null>(null)
    const [loading, setLoading] = useState<boolean>(false)
    const [sortBy, setSortBy] = useState<SortColumn>('clicks')
    const [sortDir, setSortDir] = useState<SortDir>('desc')
    const [page, setPage] = useState<number>(1)

    const load = useCallback(
        async (nextDays: number, signal: AbortSignal) => {
            setLoading(true)
            setErrorMessage(null)
            setErrorCode(null)
            try {
                const result = await fetchGscTopPages({
                    days: nextDays,
                    limit,
                    siteUrl,
                    baseUrl,
                    fetchImpl,
                    signal,
                })
                if (signal.aborted) return
                setData(result)
                setPage(1)
            } catch (e) {
                if (signal.aborted) return
                if (e instanceof GscTopPagesError) {
                    setErrorCode(e.code)
                    setErrorMessage(e.message)
                } else if (e instanceof Error) {
                    setErrorMessage(e.message)
                }
            } finally {
                if (!signal.aborted) {
                    setLoading(false)
                }
            }
        },
        [limit, siteUrl, baseUrl, fetchImpl],
    )

    useEffect(() => {
        const controller = new AbortController()
        load(days, controller.signal)
        return () => controller.abort()
    }, [days, load])

    const sortedRows = useMemo(() => {
        if (!data) return []
        const rows = [...data.rows]
        rows.sort((a, b) => {
            const left = a[sortBy]
            const right = b[sortBy]
            const dir = sortDir === 'asc' ? 1 : -1
            if (typeof left === 'number' && typeof right === 'number') {
                return (left - right) * dir
            }
            return String(left).localeCompare(String(right)) * dir
        })
        return rows
    }, [data, sortBy, sortDir])

    const totalPages = Math.max(1, Math.ceil(sortedRows.length / perPage))
    const visibleRows = sortedRows.slice((page - 1) * perPage, page * perPage)

    const handleSort = (column: SortColumn) => {
        if (sortBy === column) {
            setSortDir(sortDir === 'asc' ? 'desc' : 'asc')
        } else {
            const columnDef = COLUMNS.find((c) => c.key === column)
            setSortBy(column)
            setSortDir(columnDef?.defaultDir ?? 'desc')
        }
        setPage(1)
    }

    if (errorCode === 'base_not_installed') {
        return (
            <div className="ap-gsc-top-pages__missing-base" role="alert">
                <p>{errorMessage}</p>
                <pre>
                    <code>composer require artisanpack-ui/google</code>
                </pre>
            </div>
        )
    }

    return (
        <div className="ap-gsc-top-pages">
            <header className="ap-gsc-top-pages__header">
                <h2 className="ap-gsc-top-pages__title">Top pages</h2>
                <label className="ap-gsc-top-pages__range">
                    <span className="ap-gsc-top-pages__range-label">Range</span>
                    <select
                        value={days}
                        onChange={(event) => setDays(Number(event.target.value))}
                        disabled={loading}
                    >
                        {RANGE_OPTIONS.map((option) => (
                            <option key={option} value={option}>
                                Last {option} days
                            </option>
                        ))}
                    </select>
                </label>
            </header>

            {errorMessage ? (
                <div className="ap-gsc-top-pages__error" role="alert">
                    {errorMessage}
                </div>
            ) : visibleRows.length > 0 ? (
                <>
                    <table className="ap-gsc-top-pages__table">
                        <thead>
                            <tr>
                                {COLUMNS.map((col) => (
                                    <th key={col.key} scope="col">
                                        <button
                                            type="button"
                                            className="ap-gsc-top-pages__sort"
                                            onClick={() => handleSort(col.key)}
                                            aria-sort={
                                                sortBy === col.key
                                                    ? sortDir === 'asc'
                                                        ? 'ascending'
                                                        : 'descending'
                                                    : 'none'
                                            }
                                        >
                                            {col.label}
                                            {sortBy === col.key && (
                                                <span aria-hidden="true">{sortDir === 'asc' ? '▲' : '▼'}</span>
                                            )}
                                        </button>
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {visibleRows.map((row, index) => (
                                <tr key={`${index}:${row.page}`}>
                                    <th scope="row">
                                        <span className="ap-gsc-top-pages__page">{row.page}</span>
                                    </th>
                                    <td>{formatInt(row.clicks)}</td>
                                    <td>{formatInt(row.impressions)}</td>
                                    <td>{formatPercent(row.ctr)}%</td>
                                    <td>{row.position.toFixed(1)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <nav className="ap-gsc-top-pages__pagination" aria-label="Top pages pagination">
                        <button type="button" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1}>
                            Previous
                        </button>
                        <span>
                            Page {page} of {totalPages}
                        </span>
                        <button
                            type="button"
                            onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                            disabled={page >= totalPages}
                        >
                            Next
                        </button>
                    </nav>
                </>
            ) : (
                <p className="ap-gsc-top-pages__empty">No pages for this range yet.</p>
            )}
        </div>
    )
}

function formatInt(value: number): string {
    return new Intl.NumberFormat().format(Math.round(value))
}

function formatPercent(value: number): string {
    return (value * 100).toFixed(2)
}
