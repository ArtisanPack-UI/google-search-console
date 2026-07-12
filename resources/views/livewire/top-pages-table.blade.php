<div class="ap-gsc-top-pages">
    @unless ($baseInstalled)
        <div class="ap-gsc-top-pages__missing-base" role="alert">
            <p>
                {{ __( 'Search Console top pages require the base google package. Install it with:' ) }}
            </p>
            <pre><code>composer require artisanpack-ui/google</code></pre>
        </div>
    @else
        <header class="ap-gsc-top-pages__header">
            <h2 class="ap-gsc-top-pages__title">{{ __( 'Top pages' ) }}</h2>

            <label class="ap-gsc-top-pages__range">
                <span class="ap-gsc-top-pages__range-label">{{ __( 'Range' ) }}</span>
                <select wire:model.change="days">
                    <option value="7">{{ __( 'Last 7 days' ) }}</option>
                    <option value="28">{{ __( 'Last 28 days' ) }}</option>
                    <option value="90">{{ __( 'Last 90 days' ) }}</option>
                    <option value="180">{{ __( 'Last 180 days' ) }}</option>
                </select>
            </label>
        </header>

        @if ($errorMessage)
            <div class="ap-gsc-top-pages__error" role="alert">
                {{ $errorMessage }}
            </div>
        @elseif (count( $visibleRows ) > 0)
            <table class="ap-gsc-top-pages__table">
                <thead>
                    <tr>
                        @foreach ([ 'page' => __( 'Page' ), 'clicks' => __( 'Clicks' ), 'impressions' => __( 'Impressions' ), 'ctr' => __( 'CTR' ), 'position' => __( 'Position' ) ] as $column => $label)
                            <th scope="col">
                                <button
                                    type="button"
                                    class="ap-gsc-top-pages__sort"
                                    wire:click="sortByColumn('{{ $column }}')"
                                    aria-sort="{{ $sortBy === $column ? ( 'asc' === $sortDir ? 'ascending' : 'descending' ) : 'none' }}"
                                >
                                    {{ $label }}
                                    @if ($sortBy === $column)
                                        <span aria-hidden="true">{{ 'asc' === $sortDir ? '▲' : '▼' }}</span>
                                    @endif
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($visibleRows as $row)
                        <tr>
                            <th scope="row">
                                <span class="ap-gsc-top-pages__page">{{ $row['page'] }}</span>
                            </th>
                            <td>{{ number_format( $row['clicks'] ) }}</td>
                            <td>{{ number_format( $row['impressions'] ) }}</td>
                            <td>{{ number_format( ( $row['ctr'] ?? 0 ) * 100, 2 ) }}%</td>
                            <td>{{ number_format( $row['position'] ?? 0, 1 ) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <nav class="ap-gsc-top-pages__pagination" aria-label="{{ __( 'Top pages pagination' ) }}">
                <button
                    type="button"
                    wire:click="previousPage"
                    @if ($page <= 1) disabled @endif
                >{{ __( 'Previous' ) }}</button>
                <span>
                    {{ __( 'Page :current of :total', [ 'current' => $page, 'total' => $totalPages ] ) }}
                </span>
                <button
                    type="button"
                    wire:click="nextPage"
                    @if ($page >= $totalPages) disabled @endif
                >{{ __( 'Next' ) }}</button>
            </nav>
        @else
            <p class="ap-gsc-top-pages__empty">{{ __( 'No pages for this range yet.' ) }}</p>
        @endif
    @endunless
</div>
