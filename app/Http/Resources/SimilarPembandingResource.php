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
            'score' => $this->score ?? null,
            'distance' => $this->distance ?? null,
            'priority_rank' => $this->priority_rank ?? null,
            'is_fallback' => (bool) ($this->is_fallback ?? false),
        ]);
    }
}
