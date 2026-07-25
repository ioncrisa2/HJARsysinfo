<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimilarPembandingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = (new PembandingResource($this->resource))->toArray($request);

        return array_merge($base, [
            /** @var float|null */
            'score' => $this->score ?? null,
            /** @var float|null */
            'similarity_score' => $this->similarity_score ?? $this->score ?? null,
            /** @var float|null */
            'reference_coverage' => $this->reference_coverage ?? null,
            /** @var float|null */
            'score_coverage' => $this->score_coverage ?? null,
            'scoring_status' => $this->scoring_status ?? null,
            /** @var bool|null */
            'rankable' => $this->rankable ?? null,
            'method_version' => $this->method_version ?? null,
            /** @var int|null */
            'similarity_rank' => $this->similarity_rank ?? null,
            'eligibility_tier' => $this->eligibility_tier ?? null,
            /** @var list<string> */
            'eligibility_reasons' => $this->eligibility_reasons ?? [],
            /** @var array{score: float, components: array<string, mixed>}|null */
            'evidence_quality' => $this->evidence_quality ?? null,
            'evidence_tier' => $this->evidence_tier ?? null,
            /**
             * @var array<string, array{
             *     weight: float,
             *     applicable: bool,
             *     candidate_available: bool,
             *     similarity: float|null,
             *     contribution: float,
             *     warning: string|null,
             *     context: array<string, mixed>
             * }>
             */
            'component_scores' => $this->component_scores ?? [],
            /** @var list<string> */
            'warnings' => $this->warnings ?? [],
            'retrieval_stage' => $this->retrieval_stage ?? null,
            'fallback_reason' => $this->fallback_reason ?? null,
            'report_readiness' => $this->report_readiness ?? null,
            /** @var float|null */
            'report_completeness' => $this->report_completeness ?? null,
            /** @var list<string> */
            'report_missing_fields' => $this->report_missing_fields ?? [],
            'record_version' => $this->record_version ?? null,
            /** @var float|null */
            'distance' => $this->distance ?? null,
            /** @var int|null */
            'rank' => $this->rank ?? null,
            /** @var int|null */
            'priority_rank' => $this->priority_rank ?? null,
            'is_fallback' => (bool) ($this->is_fallback ?? false),
        ]);
    }
}
