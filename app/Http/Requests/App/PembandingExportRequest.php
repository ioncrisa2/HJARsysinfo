<?php

namespace App\Http\Requests\App;

use App\Services\Pembanding\PembandingBrowseFilterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PembandingExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['nullable', 'string', Rule::in(['excel', 'pdf', 'csv', 'geojson', 'kml'])],
            'mode' => ['nullable', 'string', Rule::in(['summary', 'detail'])],
            'profile' => ['nullable', 'string', Rule::in(['ringkas', 'lengkap', 'kontak', 'geospasial', 'audit'])],
            'scope' => ['nullable', 'string', Rule::in(['selected', 'filtered'])],
            'dataset' => ['nullable', 'string', Rule::in(['all', 'complete', 'issues'])],
            'ids' => ['nullable'],
            'columns' => ['nullable', 'array', 'max:40'],
            'columns.*' => ['string', 'max:64'],
            'province_id' => ['nullable', 'string', 'exists:provinces,id'],
            'regency_id' => ['nullable', 'string', 'exists:regencies,id'],
            'district_id' => ['nullable', 'string', 'exists:districts,id'],
            'village_id' => ['nullable', 'string', 'exists:villages,id'],
            'jenis_listing_id' => ['nullable', 'integer', 'exists:master_jenis_listing,id'],
            'jenis_objek_id' => ['nullable', 'integer', 'exists:master_jenis_objek,id'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'dari_tanggal' => ['nullable', 'date'],
            'sampai_tanggal' => ['nullable', 'date', 'after_or_equal:dari_tanggal'],
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', Rule::in([25, 50, 100])],
        ];
    }

    public function filters(PembandingBrowseFilterService $filterService): array
    {
        return $filterService->normalize($this->safe()->only($filterService->keys()));
    }
}
