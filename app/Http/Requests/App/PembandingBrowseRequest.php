<?php

namespace App\Http\Requests\App;

use App\Services\Pembanding\PembandingBrowseFilterService;
use Illuminate\Foundation\Http\FormRequest;

class PembandingBrowseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'province_id' => ['nullable', 'string', 'exists:provinces,id'],
            'regency_id' => ['nullable', 'string', 'exists:regencies,id'],
            'district_id' => ['nullable', 'string', 'exists:districts,id'],
            'village_id' => ['nullable', 'string', 'exists:villages,id'],
            'q' => ['nullable', 'string', 'max:255'],
            'dari_tanggal' => ['nullable', 'date'],
            'sampai_tanggal' => ['nullable', 'date', 'after_or_equal:dari_tanggal'],
            'jenis_listing_id' => ['nullable', 'integer', 'exists:master_jenis_listing,id'],
            'jenis_objek_id' => ['nullable', 'integer', 'exists:master_jenis_objek,id'],
            'per_page' => ['nullable', 'integer', 'in:8,16,32,64'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(PembandingBrowseFilterService $filterService): array
    {
        return $filterService->normalize(
            $this->safe()->only($filterService->keys()),
        );
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 16);
    }
}
