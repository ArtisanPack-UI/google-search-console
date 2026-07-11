import { useCallback, useEffect, useState } from 'react'
import {
    fetchGscPerformance,
    GscPerformanceError,
    type FetchPerformanceOptions,
    type GscPerformanceData,
} from '../shared/performance'

export type { GscPerformanceData }

export interface PerformanceCardProps {
    initialDays?: number
    siteUrl?: string | null
    baseUrl?: string
    fetchImpl?: FetchPerformanceOptions['fetchImpl']
}

const RANGE_OPTIONS = [7, 28, 90, 180]

export function PerformanceCard(props: PerformanceCardProps): JSX.Element {
    const { initialDays = 28, siteUrl, baseUrl, fetchImpl } = props

    const [days, setDays] = useState<number>(initialDays)
    const [data, setData] = useState<GscPerformanceData | null>(null)
    const [errorMessage, setErrorMessage] = useState<string | null>(null)
    const [errorCode, setErrorCode] = useState<string | null>(null)
    const [loading, setLoading] = useState<boolean>(false)

    const load = useCallback(
        async (nextDays: number, signal: AbortSignal) => {
            setLoading(true)
            setErrorMessage(null)
            setErrorCode(null)
            try {
                const result = await fetchGscPerformance({
                    days: nextDays,
                    siteUrl,
                    baseUrl,
                    fetchImpl,
                    signal,
                })
                if (signal.aborted) return
                setData(result)
            } catch (e) {
                if (signal.aborted) return
                if (e instanceof GscPerformanceError) {
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
        [siteUrl, baseUrl, fetchImpl],
    )

    useEffect(() => {
        const controller = new AbortController()
        load(days, controller.signal)
        return () => controller.abort()
    }, [days, load])

    if (errorCode === 'base_not_installed') {
        return (
            <div className="ap-gsc-performance__missing-base" role="alert">
                <p>{errorMessage}</p>
                <pre>
                    <code>composer require artisanpack-ui/google</code>
                </pre>
            </div>
        )
    }

    const maxClicks = data?.trend.reduce((max, row) => Math.max(max, row.clicks), 0) || 1

    return (
        <div className="ap-gsc-performance">
            <header className="ap-gsc-performance__header">
                <h2 className="ap-gsc-performance__title">Search performance</h2>
                <label className="ap-gsc-performance__range">
                    <span className="ap-gsc-performance__range-label">Range</span>
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
                <div className="ap-gsc-performance__error" role="alert">
                    {errorMessage}
                </div>
            ) : (
                <>
                    <dl className="ap-gsc-performance__totals">
                        <Tile label="Clicks" value={formatInt(data?.totals.clicks)} />
                        <Tile label="Impressions" value={formatInt(data?.totals.impressions)} />
                        <Tile label="Avg. CTR" value={`${formatPercent(data?.totals.ctr)}%`} />
                        <Tile label="Avg. position" value={formatDecimal(data?.totals.position, 1)} />
                    </dl>

                    <figure className="ap-gsc-performance__chart" aria-label="Daily clicks and impressions trend">
                        {data && data.trend.length > 0 ? (
                            <ol className="ap-gsc-performance__bars">
                                {data.trend.map((row) => {
                                    const height = Math.min(100, Math.round((row.clicks / maxClicks) * 100))
                                    return (
                                        <li
                                            key={row.date}
                                            className="ap-gsc-performance__bar"
                                            style={{ ['--ap-bar-height' as string]: `${height}%` }}
                                            title={`${row.date}: ${formatInt(row.clicks)} clicks`}
                                        >
                                            <span className="visually-hidden">
                                                {row.date} — {formatInt(row.clicks)} clicks
                                            </span>
                                        </li>
                                    )
                                })}
                            </ol>
                        ) : (
                            <p className="ap-gsc-performance__empty">No data for this range yet.</p>
                        )}
                    </figure>
                </>
            )}
        </div>
    )
}

function Tile({ label, value }: { label: string; value: string }): JSX.Element {
    return (
        <div className="ap-gsc-performance__tile">
            <dt>{label}</dt>
            <dd>{value}</dd>
        </div>
    )
}

function formatInt(value: number | undefined): string {
    return new Intl.NumberFormat().format(Math.round(value ?? 0))
}

function formatPercent(value: number | undefined): string {
    return ((value ?? 0) * 100).toFixed(2)
}

function formatDecimal(value: number | undefined, digits: number): string {
    return (value ?? 0).toFixed(digits)
}
