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
            'distance' => $this->distance ?? null,
            /** @var int|null */
            'rank' => $this->rank ?? null,
            /** @var int|null */
            'priority_rank' => $this->priority_rank ?? null,
            'is_fallback' => (bool) ($this->is_fallback ?? false),
        ]);
    }
}
