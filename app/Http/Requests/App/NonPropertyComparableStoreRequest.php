<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NonPropertyComparableStoreRequest extends FormRequest
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
            'asset_category' => ['required', Rule::in(['vehicle', 'heavy_equipment', 'barge'])],
            'asset_subtype' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'variant' => ['nullable', 'string', 'max:255'],
            'manufacture_year' => ['nullable', 'integer', 'min:1950', 'max:' . ((int) date('Y') + 1)],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],

            'listing_type' => ['required', Rule::in(['penawaran', 'transaksi'])],
            'source_platform' => ['nullable', 'string', 'max:255'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_phone' => ['nullable', 'string', 'max:64'],
            'source_url' => ['nullable', 'url', 'max:2000'],

            'location_country' => ['nullable', 'string', 'max:255'],
            'location_city' => ['required', 'string', 'max:255'],
            'location_address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'currency' => ['required', 'string', 'size:3'],
            'price' => ['required', 'numeric', 'min:0'],
            'assumed_discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'data_date' => ['required', 'date_format:Y-m-d'],

            'asset_condition' => ['required', Rule::in(['baru', 'bekas'])],
            'operational_status' => ['nullable', Rule::in(['operasional', 'tidak_operasional'])],
            'legal_document_status' => ['nullable', 'string', 'max:255'],
            'verification_status' => ['required', Rule::in(['unverified', 'partial', 'verified'])],
            'confidence_score' => ['nullable', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'vehicle_type' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('asset_category') === 'vehicle'),
                'string',
                'max:100',
            ],
            'axle_configuration' => ['nullable', 'string', 'max:20'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'transmission' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'engine_cc' => ['nullable', 'integer', 'min:0'],
            'payload_kg' => ['nullable', 'integer', 'min:0'],
            'body_type' => ['nullable', 'string', 'max:100'],
            'drive_type' => ['nullable', 'string', 'max:20'],

            'equipment_type' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('asset_category') === 'heavy_equipment'),
                'string',
                'max:100',
            ],
            'hour_meter' => ['nullable', 'integer', 'min:0'],
            'operating_weight_kg' => ['nullable', 'integer', 'min:0'],
            'bucket_capacity_m3' => ['nullable', 'numeric', 'min:0'],
            'engine_power_hp' => ['nullable', 'integer', 'min:0'],
            'undercarriage_type' => ['nullable', 'string', 'max:50'],
            'undercarriage_condition' => ['nullable', 'string', 'max:50'],
            'attachment' => ['nullable', 'string', 'max:100'],
            'service_history_note' => ['nullable', 'string', 'max:5000'],

            'barge_type' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('asset_category') === 'barge'),
                'string',
                'max:100',
            ],
            'capacity_dwt' => ['nullable', 'integer', 'min:0'],
            'loa_m' => ['nullable', 'numeric', 'min:0'],
            'beam_m' => ['nullable', 'numeric', 'min:0'],
            'draft_m' => ['nullable', 'numeric', 'min:0'],
            'gross_tonnage' => ['nullable', 'integer', 'min:0'],
            'built_year' => ['nullable', 'integer', 'min:1900', 'max:' . ((int) date('Y') + 1)],
            'shipyard' => ['nullable', 'string', 'max:255'],
            'hull_material' => ['nullable', 'string', 'max:100'],
            'class_status' => ['nullable', 'string', 'max:100'],
            'certificate_valid_until' => ['nullable', 'date_format:Y-m-d'],
            'last_docking_date' => ['nullable', 'date_format:Y-m-d'],

            'media_files' => ['nullable', 'array', 'max:20'],
            'media_files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
            'media_links' => ['nullable', 'array', 'max:20'],
            'media_links.*.external_url' => ['nullable', 'url', 'max:2000'],
            'media_links.*.caption' => ['nullable', 'string', 'max:255'],
            'removed_media_ids' => ['nullable', 'array', 'max:200'],
            'removed_media_ids.*' => ['integer', 'exists:np_comparable_media,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $mediaLinksInput = $this->input('media_links', []);
        if (! is_array($mediaLinksInput)) {
            $mediaLinksInput = [];
        }

        $normalizedMediaLinks = collect($mediaLinksInput)
            ->filter(fn ($item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'external_url' => trim((string) ($item['external_url'] ?? '')),
                'caption' => trim((string) ($item['caption'] ?? '')),
            ])
            ->values()
            ->all();

        $removedMediaInput = $this->input('removed_media_ids', []);
        if (! is_array($removedMediaInput)) {
            $removedMediaInput = [];
        }

        $normalizedRemovedMediaIds = collect($removedMediaInput)
            ->filter(fn ($item): bool => is_numeric($item))
            ->map(fn ($item): int => (int) $item)
            ->filter(fn (int $item): bool => $item > 0)
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'currency' => strtoupper((string) $this->input('currency', 'IDR')),
            'media_links' => $normalizedMediaLinks,
            'removed_media_ids' => $normalizedRemovedMediaIds,
        ]);
    }
}
