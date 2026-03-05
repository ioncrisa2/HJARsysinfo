<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\NonPropertyComparableStoreRequest;
use App\Http\Requests\App\NonPropertyComparableUpdateRequest;
use App\Models\NonPropertyComparable;
use App\Models\NonPropertyComparableMedia;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class NonPropertyComparableController extends Controller
{
    private const CATEGORY_VEHICLE = 'vehicle';
    private const CATEGORY_HEAVY_EQUIPMENT = 'heavy_equipment';
    private const CATEGORY_BARGE = 'barge';

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'asset_category' => (string) $request->input('asset_category', ''),
            'asset_subtype' => (string) $request->input('asset_subtype', ''),
            'listing_type' => (string) $request->input('listing_type', ''),
            'year' => (string) $request->input('year', ''),
            'verification_status' => (string) $request->input('verification_status', ''),
        ];

        $records = NonPropertyComparable::query()
            ->with(['vehicleSpec', 'heavyEquipmentSpec', 'bargeSpec'])
            ->withCount('media')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($nested) use ($filters) {
                    $term = '%' . $filters['q'] . '%';
                    $nested
                        ->where('comparable_code', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('variant', 'like', $term)
                        ->orWhere('source_name', 'like', $term)
                        ->orWhere('location_city', 'like', $term);
                });
            })
            ->when($filters['asset_category'] !== '', fn ($query) => $query->where('asset_category', $filters['asset_category']))
            ->when($filters['asset_subtype'] !== '', fn ($query) => $query->where('asset_subtype', $filters['asset_subtype']))
            ->when($filters['listing_type'] !== '', fn ($query) => $query->where('listing_type', $filters['listing_type']))
            ->when($filters['year'] !== '', fn ($query) => $query->where('manufacture_year', (int) $filters['year']))
            ->when(
                $filters['verification_status'] !== '',
                fn ($query) => $query->where('verification_status', $filters['verification_status'])
            )
            ->orderByDesc('data_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->through(fn (NonPropertyComparable $record): array => [
                'id' => $record->id,
                'comparable_code' => $record->comparable_code,
                'asset_category' => $record->asset_category,
                'asset_subtype' => $record->asset_subtype,
                'brand' => $record->brand,
                'model' => $record->model,
                'variant' => $record->variant,
                'manufacture_year' => $record->manufacture_year,
                'location_city' => $record->location_city,
                'price' => $record->asking_price ?? $record->transaction_price,
                'data_date' => optional($record->data_date)->toDateString(),
                'verification_status' => $record->verification_status,
                'listing_type' => $record->listing_type,
                'usage_metric' => $this->usageMetricLabel($record),
                'media_count' => $record->media_count,
            ])
            ->withQueryString();

        return Inertia::render('NonProperty/Index', [
            'filters' => $filters,
            'records' => $records,
            'stats' => [
                'total' => NonPropertyComparable::query()->count(),
                'verified' => NonPropertyComparable::query()
                    ->where('verification_status', 'verified')
                    ->count(),
                'last_data_date' => NonPropertyComparable::query()->max('data_date'),
                'vehicle_total' => NonPropertyComparable::query()
                    ->where('asset_category', self::CATEGORY_VEHICLE)
                    ->count(),
                'heavy_total' => NonPropertyComparable::query()
                    ->where('asset_category', self::CATEGORY_HEAVY_EQUIPMENT)
                    ->count(),
                'barge_total' => NonPropertyComparable::query()
                    ->where('asset_category', self::CATEGORY_BARGE)
                    ->count(),
            ],
            'options' => $this->formOptions(),
            'can' => [
                'create' => (bool) $request->user()?->can('create_data::non_property_comparable'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('NonProperty/Create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(NonPropertyComparableStoreRequest $request): RedirectResponse
    {
        [$coreData, $vehicleData, $heavyData, $bargeData] = $this->splitPayload($request->validated());
        $coreData = $this->normalizeCoreData($coreData);

        $category = (string) $coreData['asset_category'];
        $userId = $request->user()?->id;

        $comparable = NonPropertyComparable::query()->create(array_merge(
            $coreData,
            [
                'comparable_code' => $this->generateComparableCode($category),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        ));

        $this->persistCategorySpec($comparable, $category, $vehicleData, $heavyData, $bargeData);
        $this->syncMediaFromRequest($request, $comparable);

        return redirect()
            ->route('home.non-properti.show', $comparable)
            ->with('success', 'Data Non Properti berhasil ditambahkan.');
    }

    public function show(NonPropertyComparable $comparable): Response
    {
        $comparable->load([
            'vehicleSpec',
            'heavyEquipmentSpec',
            'bargeSpec',
            'media',
            'creator:id,name',
            'updater:id,name',
            'deletedBy:id,name',
        ]);

        return Inertia::render('NonProperty/Show', [
            'record' => $this->showPayload($comparable),
            'can' => [
                'edit' => (bool) request()->user()?->can('update_data::non_property_comparable'),
                'delete' => (bool) request()->user()?->can('delete_data::non_property_comparable'),
                'view_history' => (bool) request()->user()?->can('view_any_data::non_property_comparable'),
            ],
        ]);
    }

    public function edit(NonPropertyComparable $comparable): Response
    {
        $comparable->load(['vehicleSpec', 'heavyEquipmentSpec', 'bargeSpec', 'media']);

        return Inertia::render('NonProperty/Edit', [
            'record' => $this->editPayload($comparable),
            'options' => $this->formOptions(),
        ]);
    }

    public function update(
        NonPropertyComparableUpdateRequest $request,
        NonPropertyComparable $comparable
    ): RedirectResponse {
        [$coreData, $vehicleData, $heavyData, $bargeData] = $this->splitPayload($request->validated());
        $coreData = $this->normalizeCoreData($coreData);

        $category = (string) $coreData['asset_category'];

        $comparable->update(array_merge($coreData, [
            'updated_by' => $request->user()?->id,
        ]));

        $this->persistCategorySpec($comparable, $category, $vehicleData, $heavyData, $bargeData);
        $this->syncMediaFromRequest($request, $comparable);

        return redirect()
            ->route('home.non-properti.show', $comparable)
            ->with('success', 'Data Non Properti berhasil diperbarui.');
    }

    public function history(NonPropertyComparable $comparable, Request $request): Response
    {
        $activities = $comparable->activities()
            ->latest()
            ->with('causer:id,name,email')
            ->take(100)
            ->get()
            ->map(function ($activity): array {
                $propertiesRaw = $activity->properties;

                if ($propertiesRaw instanceof \Illuminate\Support\Collection) {
                    $properties = $propertiesRaw->all();
                } elseif (is_array($propertiesRaw)) {
                    $properties = $propertiesRaw;
                } else {
                    $properties = [];
                }

                $attributes = data_get($properties, 'attributes', []);
                $old = data_get($properties, 'old', []);

                if (! is_array($attributes)) {
                    $attributes = [];
                }

                if (! is_array($old)) {
                    $old = [];
                }

                $changes = [];
                foreach ($attributes as $key => $newVal) {
                    $oldVal = $old[$key] ?? null;
                    if ($newVal === $oldVal) {
                        continue;
                    }
                    $changes[] = [
                        'field' => $key,
                        'old' => $oldVal,
                        'new' => $newVal,
                    ];
                }

                if ($changes === []) {
                    foreach ($properties as $key => $value) {
                        if (in_array($key, ['attributes', 'old'], true)) {
                            continue;
                        }

                        $changes[] = [
                            'field' => $key,
                            'old' => null,
                            'new' => $value,
                        ];
                    }
                }

                return [
                    'id' => $activity->id,
                    'event' => $activity->event ?? $activity->description,
                    'description' => $activity->description,
                    'causer' => $activity->causer?->name ?? 'Sistem',
                    'causer_email' => $activity->causer?->email,
                    'created_at' => $activity->created_at?->toDateTimeString(),
                    'changes' => $changes,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('NonProperty/History', [
            'record' => [
                'id' => $comparable->id,
                'comparable_code' => $comparable->comparable_code,
                'asset_category' => $comparable->asset_category,
                'unit_name' => trim(implode(' ', array_filter([
                    $comparable->brand,
                    $comparable->model,
                    $comparable->variant,
                ]))),
            ],
            'activities' => $activities,
            'can' => [
                'view_detail' => (bool) $request->user()?->can('view_any_data::non_property_comparable'),
            ],
        ]);
    }

    public function destroy(Request $request, NonPropertyComparable $comparable): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Alasan penghapusan wajib diisi.',
        ]);

        $reason = trim((string) $data['reason']);
        $userId = $request->user()?->id;

        $comparable->update([
            'updated_by' => $userId,
            'deleted_by_id' => $userId,
            'deleted_reason' => $reason,
        ]);
        $comparable->delete();

        return redirect()
            ->route('home.non-properti.index')
            ->with('success', 'Data Non Properti berhasil dihapus.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>, 3: array<string, mixed>}
     */
    private function splitPayload(array $payload): array
    {
        $vehicleKeys = [
            'vehicle_type',
            'axle_configuration',
            'odometer_km',
            'transmission',
            'fuel_type',
            'engine_cc',
            'payload_kg',
            'body_type',
            'drive_type',
        ];

        $heavyKeys = [
            'equipment_type',
            'hour_meter',
            'operating_weight_kg',
            'bucket_capacity_m3',
            'engine_power_hp',
            'undercarriage_type',
            'undercarriage_condition',
            'attachment',
            'service_history_note',
        ];

        $bargeKeys = [
            'barge_type',
            'capacity_dwt',
            'loa_m',
            'beam_m',
            'draft_m',
            'gross_tonnage',
            'built_year',
            'shipyard',
            'hull_material',
            'class_status',
            'certificate_valid_until',
            'last_docking_date',
        ];

        $vehicleData = array_intersect_key($payload, array_flip($vehicleKeys));
        $heavyData = array_intersect_key($payload, array_flip($heavyKeys));
        $bargeData = array_intersect_key($payload, array_flip($bargeKeys));

        $mediaKeys = ['media_files', 'media_links', 'removed_media_ids'];
        $specKeys = array_merge($vehicleKeys, $heavyKeys, $bargeKeys, $mediaKeys);
        $coreData = array_diff_key($payload, array_flip($specKeys));

        return [$coreData, $vehicleData, $heavyData, $bargeData];
    }

    private function persistCategorySpec(
        NonPropertyComparable $comparable,
        string $category,
        array $vehicleData,
        array $heavyData,
        array $bargeData
    ): void {
        if ($category === self::CATEGORY_VEHICLE) {
            $comparable->vehicleSpec()->updateOrCreate(
                ['np_comparable_id' => $comparable->id],
                $vehicleData
            );
            $comparable->heavyEquipmentSpec()->delete();
            $comparable->bargeSpec()->delete();

            return;
        }

        if ($category === self::CATEGORY_HEAVY_EQUIPMENT) {
            $comparable->heavyEquipmentSpec()->updateOrCreate(
                ['np_comparable_id' => $comparable->id],
                $heavyData
            );
            $comparable->vehicleSpec()->delete();
            $comparable->bargeSpec()->delete();

            return;
        }

        $comparable->bargeSpec()->updateOrCreate(
            ['np_comparable_id' => $comparable->id],
            $bargeData
        );
        $comparable->vehicleSpec()->delete();
        $comparable->heavyEquipmentSpec()->delete();
    }

    /**
     * @param array<string, mixed> $coreData
     * @return array<string, mixed>
     */
    private function normalizeCoreData(array $coreData): array
    {
        $price = $coreData['price'] ?? null;
        unset($coreData['price']);

        $coreData['asking_price'] = $price;
        $coreData['transaction_price'] = null;

        return $coreData;
    }

    /**
     * @return array<string, mixed>
     */
    private function showPayload(NonPropertyComparable $comparable): array
    {
        return [
            'id' => $comparable->id,
            'comparable_code' => $comparable->comparable_code,
            'asset_category' => $comparable->asset_category,
            'asset_subtype' => $comparable->asset_subtype,
            'brand' => $comparable->brand,
            'model' => $comparable->model,
            'variant' => $comparable->variant,
            'manufacture_year' => $comparable->manufacture_year,
            'serial_number' => $comparable->serial_number,
            'registration_number' => $comparable->registration_number,
            'listing_type' => $comparable->listing_type,
            'source_platform' => $comparable->source_platform,
            'source_name' => $comparable->source_name,
            'source_phone' => $comparable->source_phone,
            'source_url' => $comparable->source_url,
            'location_country' => $comparable->location_country,
            'location_city' => $comparable->location_city,
            'location_address' => $comparable->location_address,
            'latitude' => $comparable->latitude,
            'longitude' => $comparable->longitude,
            'currency' => $comparable->currency,
            'price' => $comparable->asking_price ?? $comparable->transaction_price,
            'assumed_discount_percent' => $comparable->assumed_discount_percent,
            'data_date' => optional($comparable->data_date)->toDateString(),
            'asset_condition' => $comparable->asset_condition,
            'operational_status' => $comparable->operational_status,
            'legal_document_status' => $comparable->legal_document_status,
            'verification_status' => $comparable->verification_status,
            'confidence_score' => $comparable->confidence_score,
            'notes' => $comparable->notes,
            'vehicle' => [
                'vehicle_type' => $comparable->vehicleSpec?->vehicle_type,
                'axle_configuration' => $comparable->vehicleSpec?->axle_configuration,
                'odometer_km' => $comparable->vehicleSpec?->odometer_km,
                'transmission' => $comparable->vehicleSpec?->transmission,
                'fuel_type' => $comparable->vehicleSpec?->fuel_type,
                'engine_cc' => $comparable->vehicleSpec?->engine_cc,
                'payload_kg' => $comparable->vehicleSpec?->payload_kg,
                'body_type' => $comparable->vehicleSpec?->body_type,
                'drive_type' => $comparable->vehicleSpec?->drive_type,
            ],
            'heavy_equipment' => [
                'equipment_type' => $comparable->heavyEquipmentSpec?->equipment_type,
                'hour_meter' => $comparable->heavyEquipmentSpec?->hour_meter,
                'operating_weight_kg' => $comparable->heavyEquipmentSpec?->operating_weight_kg,
                'bucket_capacity_m3' => $comparable->heavyEquipmentSpec?->bucket_capacity_m3,
                'engine_power_hp' => $comparable->heavyEquipmentSpec?->engine_power_hp,
                'undercarriage_type' => $comparable->heavyEquipmentSpec?->undercarriage_type,
                'undercarriage_condition' => $comparable->heavyEquipmentSpec?->undercarriage_condition,
                'attachment' => $comparable->heavyEquipmentSpec?->attachment,
                'service_history_note' => $comparable->heavyEquipmentSpec?->service_history_note,
            ],
            'barge' => [
                'barge_type' => $comparable->bargeSpec?->barge_type,
                'capacity_dwt' => $comparable->bargeSpec?->capacity_dwt,
                'loa_m' => $comparable->bargeSpec?->loa_m,
                'beam_m' => $comparable->bargeSpec?->beam_m,
                'draft_m' => $comparable->bargeSpec?->draft_m,
                'gross_tonnage' => $comparable->bargeSpec?->gross_tonnage,
                'built_year' => $comparable->bargeSpec?->built_year,
                'shipyard' => $comparable->bargeSpec?->shipyard,
                'hull_material' => $comparable->bargeSpec?->hull_material,
                'class_status' => $comparable->bargeSpec?->class_status,
                'certificate_valid_until' => optional($comparable->bargeSpec?->certificate_valid_until)->toDateString(),
                'last_docking_date' => optional($comparable->bargeSpec?->last_docking_date)->toDateString(),
            ],
            'media' => $this->mediaPayload($comparable),
            'created_by' => $comparable->creator?->name,
            'updated_by' => $comparable->updater?->name,
            'deleted_by' => $comparable->deletedBy?->name,
            'deleted_reason' => $comparable->deleted_reason,
            'created_at' => optional($comparable->created_at)->toDateTimeString(),
            'updated_at' => optional($comparable->updated_at)->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function editPayload(NonPropertyComparable $comparable): array
    {
        return [
            'id' => $comparable->id,
            'asset_category' => $comparable->asset_category,
            'asset_subtype' => $comparable->asset_subtype,
            'brand' => $comparable->brand,
            'model' => $comparable->model,
            'variant' => $comparable->variant,
            'manufacture_year' => $comparable->manufacture_year,
            'serial_number' => $comparable->serial_number,
            'registration_number' => $comparable->registration_number,
            'listing_type' => $comparable->listing_type,
            'source_platform' => $comparable->source_platform,
            'source_name' => $comparable->source_name,
            'source_phone' => $comparable->source_phone,
            'source_url' => $comparable->source_url,
            'location_country' => $comparable->location_country,
            'location_city' => $comparable->location_city,
            'location_address' => $comparable->location_address,
            'latitude' => $comparable->latitude,
            'longitude' => $comparable->longitude,
            'currency' => $comparable->currency,
            'price' => $comparable->asking_price ?? $comparable->transaction_price,
            'assumed_discount_percent' => $comparable->assumed_discount_percent,
            'data_date' => optional($comparable->data_date)->toDateString(),
            'asset_condition' => $comparable->asset_condition,
            'operational_status' => $comparable->operational_status,
            'legal_document_status' => $comparable->legal_document_status,
            'verification_status' => $comparable->verification_status,
            'confidence_score' => $comparable->confidence_score,
            'notes' => $comparable->notes,
            'vehicle_type' => $comparable->vehicleSpec?->vehicle_type,
            'axle_configuration' => $comparable->vehicleSpec?->axle_configuration,
            'odometer_km' => $comparable->vehicleSpec?->odometer_km,
            'transmission' => $comparable->vehicleSpec?->transmission,
            'fuel_type' => $comparable->vehicleSpec?->fuel_type,
            'engine_cc' => $comparable->vehicleSpec?->engine_cc,
            'payload_kg' => $comparable->vehicleSpec?->payload_kg,
            'body_type' => $comparable->vehicleSpec?->body_type,
            'drive_type' => $comparable->vehicleSpec?->drive_type,
            'equipment_type' => $comparable->heavyEquipmentSpec?->equipment_type,
            'hour_meter' => $comparable->heavyEquipmentSpec?->hour_meter,
            'operating_weight_kg' => $comparable->heavyEquipmentSpec?->operating_weight_kg,
            'bucket_capacity_m3' => $comparable->heavyEquipmentSpec?->bucket_capacity_m3,
            'engine_power_hp' => $comparable->heavyEquipmentSpec?->engine_power_hp,
            'undercarriage_type' => $comparable->heavyEquipmentSpec?->undercarriage_type,
            'undercarriage_condition' => $comparable->heavyEquipmentSpec?->undercarriage_condition,
            'attachment' => $comparable->heavyEquipmentSpec?->attachment,
            'service_history_note' => $comparable->heavyEquipmentSpec?->service_history_note,
            'barge_type' => $comparable->bargeSpec?->barge_type,
            'capacity_dwt' => $comparable->bargeSpec?->capacity_dwt,
            'loa_m' => $comparable->bargeSpec?->loa_m,
            'beam_m' => $comparable->bargeSpec?->beam_m,
            'draft_m' => $comparable->bargeSpec?->draft_m,
            'gross_tonnage' => $comparable->bargeSpec?->gross_tonnage,
            'built_year' => $comparable->bargeSpec?->built_year,
            'shipyard' => $comparable->bargeSpec?->shipyard,
            'hull_material' => $comparable->bargeSpec?->hull_material,
            'class_status' => $comparable->bargeSpec?->class_status,
            'certificate_valid_until' => optional($comparable->bargeSpec?->certificate_valid_until)->toDateString(),
            'last_docking_date' => optional($comparable->bargeSpec?->last_docking_date)->toDateString(),
            'media' => $this->mediaPayload($comparable),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'asset_categories' => [
                ['label' => 'Kendaraan', 'value' => self::CATEGORY_VEHICLE],
                ['label' => 'Alat Berat', 'value' => self::CATEGORY_HEAVY_EQUIPMENT],
                ['label' => 'Tongkang', 'value' => self::CATEGORY_BARGE],
            ],
            'asset_subtypes_by_category' => [
                self::CATEGORY_VEHICLE => [
                    ['label' => 'Mobil Penumpang', 'value' => 'mobil_penumpang'],
                    ['label' => 'Pickup', 'value' => 'pickup'],
                    ['label' => 'Truck Tractor', 'value' => 'truck_tractor'],
                    ['label' => 'Dump Truck', 'value' => 'dump_truck'],
                    ['label' => 'Lainnya', 'value' => 'lainnya_kendaraan'],
                ],
                self::CATEGORY_HEAVY_EQUIPMENT => [
                    ['label' => 'Excavator', 'value' => 'excavator'],
                    ['label' => 'Bulldozer', 'value' => 'bulldozer'],
                    ['label' => 'Wheel Loader', 'value' => 'wheel_loader'],
                    ['label' => 'Motor Grader', 'value' => 'motor_grader'],
                    ['label' => 'Crane', 'value' => 'crane'],
                    ['label' => 'Lainnya', 'value' => 'lainnya_alat_berat'],
                ],
                self::CATEGORY_BARGE => [
                    ['label' => 'General Cargo Barge', 'value' => 'general_cargo_barge'],
                    ['label' => 'Deck Barge', 'value' => 'deck_barge'],
                    ['label' => 'Hopper Barge', 'value' => 'hopper_barge'],
                    ['label' => 'Tank Barge', 'value' => 'tank_barge'],
                    ['label' => 'Lainnya', 'value' => 'lainnya_tongkang'],
                ],
            ],
            'asset_subtypes' => [
                ['label' => 'Mobil Penumpang', 'value' => 'mobil_penumpang', 'category' => self::CATEGORY_VEHICLE],
                ['label' => 'Pickup', 'value' => 'pickup', 'category' => self::CATEGORY_VEHICLE],
                ['label' => 'Truck Tractor', 'value' => 'truck_tractor', 'category' => self::CATEGORY_VEHICLE],
                ['label' => 'Dump Truck', 'value' => 'dump_truck', 'category' => self::CATEGORY_VEHICLE],
                ['label' => 'Excavator', 'value' => 'excavator', 'category' => self::CATEGORY_HEAVY_EQUIPMENT],
                ['label' => 'Bulldozer', 'value' => 'bulldozer', 'category' => self::CATEGORY_HEAVY_EQUIPMENT],
                ['label' => 'Wheel Loader', 'value' => 'wheel_loader', 'category' => self::CATEGORY_HEAVY_EQUIPMENT],
                ['label' => 'Motor Grader', 'value' => 'motor_grader', 'category' => self::CATEGORY_HEAVY_EQUIPMENT],
                ['label' => 'Crane', 'value' => 'crane', 'category' => self::CATEGORY_HEAVY_EQUIPMENT],
                ['label' => 'General Cargo Barge', 'value' => 'general_cargo_barge', 'category' => self::CATEGORY_BARGE],
                ['label' => 'Deck Barge', 'value' => 'deck_barge', 'category' => self::CATEGORY_BARGE],
                ['label' => 'Hopper Barge', 'value' => 'hopper_barge', 'category' => self::CATEGORY_BARGE],
                ['label' => 'Tank Barge', 'value' => 'tank_barge', 'category' => self::CATEGORY_BARGE],
            ],
            'listing_types' => [
                ['label' => 'Penawaran', 'value' => 'penawaran'],
                ['label' => 'Transaksi', 'value' => 'transaksi'],
            ],
            'asset_conditions' => [
                ['label' => 'Baru', 'value' => 'baru'],
                ['label' => 'Bekas', 'value' => 'bekas'],
            ],
            'operational_statuses' => [
                ['label' => 'Operasional', 'value' => 'operasional'],
                ['label' => 'Tidak Operasional', 'value' => 'tidak_operasional'],
            ],
            'verification_statuses' => [
                ['label' => 'Belum Verifikasi', 'value' => 'unverified'],
                ['label' => 'Verifikasi Parsial', 'value' => 'partial'],
                ['label' => 'Terverifikasi', 'value' => 'verified'],
            ],
            'transmission_types' => [
                ['label' => 'Manual', 'value' => 'manual'],
                ['label' => 'Automatic', 'value' => 'automatic'],
                ['label' => 'Manual/MT', 'value' => 'manual_mt'],
            ],
            'fuel_types' => [
                ['label' => 'Solar', 'value' => 'solar'],
                ['label' => 'Bensin', 'value' => 'bensin'],
                ['label' => 'Listrik', 'value' => 'listrik'],
                ['label' => 'Hybrid', 'value' => 'hybrid'],
            ],
            'undercarriage_types' => [
                ['label' => 'Track/Crawler', 'value' => 'track'],
                ['label' => 'Wheel', 'value' => 'wheel'],
            ],
            'hull_material_options' => [
                ['label' => 'Steel', 'value' => 'steel'],
                ['label' => 'Aluminium', 'value' => 'aluminium'],
                ['label' => 'Composite', 'value' => 'composite'],
            ],
        ];
    }

    private function syncMediaFromRequest(Request $request, NonPropertyComparable $comparable): void
    {
        $user = $request->user();
        $userId = $user?->id;

        $removedIds = collect($request->input('removed_media_ids', []))
            ->filter(fn ($value): bool => is_numeric($value))
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values();

        $removedCount = 0;
        if ($removedIds->isNotEmpty()) {
            $existingMedia = $comparable->media()
                ->whereIn('id', $removedIds->all())
                ->get();

            foreach ($existingMedia as $media) {
                if ($media->file_path) {
                    Storage::disk('public')->delete($media->file_path);
                }
            }

            $removedCount = $existingMedia->count();

            if ($removedCount > 0) {
                $comparable->media()
                    ->whereIn('id', $existingMedia->pluck('id')->all())
                    ->delete();
            }
        }

        $nextSortOrder = ((int) $comparable->media()->max('sort_order')) + 1;
        $addedFileCount = 0;
        foreach (collect($request->file('media_files', []))->filter() as $file) {
            $path = $file->store('non_property_comparables', 'public');
            $mimeType = (string) $file->getMimeType();
            $mediaType = Str::startsWith($mimeType, 'image/') ? 'image' : 'document';

            $comparable->media()->create([
                'media_type' => $mediaType,
                'file_path' => $path,
                'caption' => null,
                'sort_order' => $nextSortOrder,
                'uploaded_by' => $userId,
            ]);

            $nextSortOrder++;
            $addedFileCount++;
        }

        $addedLinkCount = 0;
        $mediaLinks = $request->input('media_links', []);
        if (is_array($mediaLinks)) {
            foreach ($mediaLinks as $link) {
                $externalUrl = trim((string) data_get($link, 'external_url'));
                if ($externalUrl === '') {
                    continue;
                }

                $caption = trim((string) data_get($link, 'caption'));

                $comparable->media()->create([
                    'media_type' => 'link',
                    'external_url' => $externalUrl,
                    'caption' => $caption !== '' ? $caption : null,
                    'sort_order' => $nextSortOrder,
                    'uploaded_by' => $userId,
                ]);

                $nextSortOrder++;
                $addedLinkCount++;
            }
        }

        if ($removedCount > 0) {
            $this->logManualActivity(
                comparable: $comparable,
                user: $user,
                event: 'media_removed',
                description: 'Media comparables dihapus',
                properties: [
                    'removed_count' => $removedCount,
                    'removed_media_ids' => $removedIds->all(),
                ],
            );
        }

        if ($addedFileCount > 0 || $addedLinkCount > 0) {
            $this->logManualActivity(
                comparable: $comparable,
                user: $user,
                event: 'media_added',
                description: 'Media comparables ditambahkan',
                properties: [
                    'added_file_count' => $addedFileCount,
                    'added_link_count' => $addedLinkCount,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mediaPayload(NonPropertyComparable $comparable): array
    {
        return $comparable->media
            ->map(function (NonPropertyComparableMedia $media): array {
                $fileUrl = $media->file_path
                    ? Storage::disk('public')->url($media->file_path)
                    : null;

                return [
                    'id' => $media->id,
                    'media_type' => $media->media_type,
                    'caption' => $media->caption,
                    'external_url' => $media->external_url,
                    'file_path' => $media->file_path,
                    'file_url' => $fileUrl,
                    'file_name' => $media->file_path ? basename($media->file_path) : null,
                    'sort_order' => $media->sort_order,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function logManualActivity(
        NonPropertyComparable $comparable,
        ?Authenticatable $user,
        string $event,
        string $description,
        array $properties = []
    ): void {
        $logger = activity('non_property_comparable')
            ->performedOn($comparable)
            ->event($event);

        if ($user instanceof User) {
            $logger->causedBy($user);
        }

        if ($properties !== []) {
            $logger->withProperties($properties);
        }

        $logger->log($description);
    }

    private function generateComparableCode(string $category): string
    {
        $prefix = match ($category) {
            self::CATEGORY_HEAVY_EQUIPMENT => 'NPH',
            self::CATEGORY_BARGE => 'NPB',
            default => 'NPV',
        };

        $base = $prefix . '-' . now()->format('Ymd');

        do {
            $code = $base . '-' . Str::upper(Str::random(4));
        } while (NonPropertyComparable::query()->where('comparable_code', $code)->exists());

        return $code;
    }

    private function usageMetricLabel(NonPropertyComparable $record): string
    {
        if ($record->asset_category === self::CATEGORY_HEAVY_EQUIPMENT) {
            return $record->heavyEquipmentSpec?->hour_meter
                ? $record->heavyEquipmentSpec->hour_meter . ' jam'
                : '-';
        }

        if ($record->asset_category === self::CATEGORY_BARGE) {
            return $record->bargeSpec?->capacity_dwt
                ? $record->bargeSpec->capacity_dwt . ' DWT'
                : '-';
        }

        return $record->vehicleSpec?->odometer_km
            ? $record->vehicleSpec->odometer_km . ' km'
            : '-';
    }
}
