<div
    class="ap-gsc-performance"
    @if ($baseInstalled) wire:poll.60s="refresh" @endif
>
    @unless ($baseInstalled)
        <div class="ap-gsc-performance__missing-base" role="alert">
            <p>
                {{ __( 'The Search Console performance card requires the base google package. Install it with:' ) }}
            </p>
            <pre><code>composer require artisanpack-ui/google</code></pre>
        </div>
    @else
        <header class="ap-gsc-performance__header">
            <h2 class="ap-gsc-performance__title">{{ __( 'Search performance' ) }}</h2>

            <label class="ap-gsc-performance__range">
                <span class="ap-gsc-performance__range-label">{{ __( 'Range' ) }}</span>
                <select wire:model.change="days">
                    <option value="7">{{ __( 'Last 7 days' ) }}</option>
                    <option value="28">{{ __( 'Last 28 days' ) }}</option>
                    <option value="90">{{ __( 'Last 90 days' ) }}</option>
                    <option value="180">{{ __( 'Last 180 days' ) }}</option>
                </select>
            </label>
        </header>

        @if ($errorMessage)
            <div class="ap-gsc-performance__error" role="alert">
                {{ $errorMessage }}
            </div>
        @elseif (! $hasData)
            <p class="ap-gsc-performance__empty">{{ __( 'No data for this range yet.' ) }}</p>
        @else
            <dl class="ap-gsc-performance__totals">
                <div class="ap-gsc-performance__tile">
                    <dt>{{ __( 'Clicks' ) }}</dt>
                    <dd>{{ number_format( $totals['clicks'] ?? 0 ) }}</dd>
                </div>
                <div class="ap-gsc-performance__tile">
                    <dt>{{ __( 'Impressions' ) }}</dt>
                    <dd>{{ number_format( $totals['impressions'] ?? 0 ) }}</dd>
                </div>
                <div class="ap-gsc-performance__tile">
                    <dt>{{ __( 'Avg. CTR' ) }}</dt>
                    <dd>{{ number_format( ( $totals['ctr'] ?? 0 ) * 100, 2 ) }}%</dd>
                </div>
                <div class="ap-gsc-performance__tile">
                    <dt>{{ __( 'Avg. position' ) }}</dt>
                    <dd>{{ number_format( $totals['position'] ?? 0, 1 ) }}</dd>
                </div>
            </dl>

            <figure
                class="ap-gsc-performance__chart"
                aria-label="{{ __( 'Daily clicks and impressions trend' ) }}"
                data-trend="{{ json_encode( $trend, JSON_UNESCAPED_SLASHES ) }}"
            >
                @if (count( $trend ) > 0)
                    @php
                        $maxClicks = 1;
                        foreach ( $trend as $row ) {
                            $rowClicks = (float) $row['clicks'];
                            if ( $rowClicks > $maxClicks ) {
                                $maxClicks = $rowClicks;
                            }
                        }
                    @endphp
                    <ol class="ap-gsc-performance__bars" role="list">
                        @foreach ($trend as $row)
                            @php
                                $height     = min( 100, (int) round( $row['clicks'] / $maxClicks * 100 ) );
                                $formatted  = number_format( $row['clicks'] );
                                $tooltip    = trans_choice( ':date: :count click|:date: :count clicks', $row['clicks'], [ 'date' => $row['date'], 'count' => $formatted ] );
                                $aria       = trans_choice( ':date — :count click|:date — :count clicks', $row['clicks'], [ 'date' => $row['date'], 'count' => $formatted ] );
                            @endphp
                            <li
                                class="ap-gsc-performance__bar"
                                style="--ap-bar-height: {{ $height }}%"
                                title="{{ $tooltip }}"
                            >
                                <span class="visually-hidden">{{ $aria }}</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="ap-gsc-performance__empty">{{ __( 'No data for this range yet.' ) }}</p>
                @endif
            </figure>
        @endif
    @endunless
</div>
