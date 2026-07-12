<?php

/**
 * Immutable description of a Search Console searchAnalytics.query request.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\GoogleSearchConsole\Reporting;

/**
 * Builder-friendly value object describing the dimensions, date range,
 * and row limit a caller wants to pull from the Search Console API's
 * searchAnalytics.query endpoint. The shape maps directly onto the POST
 * body.
 *
 * Metrics (clicks, impressions, ctr, position) are always returned by
 * the API, so callers only specify dimensions, date range, filters,
 * and paging.
 *
 * @package    ArtisanPack_UI
 * @subpackage GoogleSearchConsole
 *
 * @since 1.0.0
 */
final class SearchAnalyticsRequest
{
    /**
     * Search Console caps rowLimit at 25,000 per request. Callers who
     * need more should paginate via `startRow`.
     */
    public const MAX_ROW_LIMIT = 25000;

    /**
     * Search Console's searchAnalytics.query endpoint always returns
     * rows sorted by clicks descending — the API does NOT accept an
     * orderBys/sort field. Callers that need a different ordering
     * should re-sort the parsed response client-side.
     *
     * @param  list<string>  $dimensions
     * @param  list<array<string, mixed>>  $dimensionFilterGroups  Raw filter groups per Search Console spec.
     */
    public function __construct(
        public readonly DateRange $dateRange,
        public readonly array $dimensions = [],
        public readonly array $dimensionFilterGroups = [],
        public readonly ?int $rowLimit = null,
        public readonly ?int $startRow = null,
        public readonly ?string $searchType = null,
        public readonly ?string $dataState = null,
    ) {
    }

    /**
     * Serialize to the JSON payload the Search Console API's
     * searchAnalytics.query endpoint expects.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        $payload = [
            'startDate' => $this->dateRange->startDate,
            'endDate'   => $this->dateRange->endDate,
        ];

        if ( [] !== $this->dimensions ) {
            $payload['dimensions'] = array_values( $this->dimensions );
        }

        if ( [] !== $this->dimensionFilterGroups ) {
            $payload['dimensionFilterGroups'] = array_values( $this->dimensionFilterGroups );
        }

        if ( null !== $this->rowLimit ) {
            $payload['rowLimit'] = min( $this->rowLimit, self::MAX_ROW_LIMIT );
        }

        if ( null !== $this->startRow ) {
            $payload['startRow'] = $this->startRow;
        }

        if ( null !== $this->searchType ) {
            $payload['type'] = $this->searchType;
        }

        if ( null !== $this->dataState ) {
            $payload['dataState'] = $this->dataState;
        }

        return $payload;
    }
}
