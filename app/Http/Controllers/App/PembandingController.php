<?php

namespace App\Http\Controllers\App;

use App\Actions\Pembanding\SavePembandingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\PembandingBrowseRequest;
use App\Http\Requests\App\PembandingStoreRequest;
use App\Http\Requests\App\PembandingUpdateRequest;
use App\Models\District;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\Pembanding;
use App\Models\PembandingDeleteRequest;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use App\Models\Village;
use App\Services\Pembanding\PembandingBrowseFilterService;
use App\Services\Pembanding\PembandingFormOptionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PembandingController extends Controller
{
    public function __construct(private readonly PembandingFormOptionsService $formOptionsService) {}

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(PembandingBrowseRequest $request, PembandingBrowseFilterService $filterService): Response
    {
        Gate::authorize('viewAny', Pembanding::class);

        $filters = $request->filters($filterService);

        $records = $filterService
            ->apply(Pembanding::query(), $filters)
            // FIX #4: Eager load semua relasi yang diakses di through() untuk menghindari N+1
            ->with([
                'jenisListing:id,slug,name',
                'jenisObjek:id,name',
                'village:id,name,district_id',
                'district:id,name,regency_id',
                'regency:id,name',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($request->perPage())
            ->through(fn (Pembanding $record): array => [
                'id' => $record->id,
                'alamat_data' => $record->alamat_data,
                'harga' => $record->harga,
                'is_sewa' => $record->is_sewa,
                'jangka_waktu_sewa' => $record->jangka_waktu_sewa,
                'satuan_waktu_sewa' => $record->satuan_waktu_sewa,
                'sewa_periode_label' => $record->sewa_periode_label,
                'tanggal_data' => $record->tanggal_data,
                'created_at' => optional($record->created_at)->toDateTimeString(),
                'latitude' => $record->latitude,
                'longitude' => $record->longitude,
                'jenis_listing' => $record->jenisListing?->name,
                'jenis_objek' => $record->jenisObjek?->name,
                'image_url' => $record->image_path,
                'location' => collect([
                    $record->village?->name,
                    $record->district?->name,
                    $record->regency?->name,
                ])->filter()->implode(', '),
                'can_update' => $request->user()->can('update', $record),
            ])
            ->withQueryString();

        return Inertia::render('Pembanding/Index', [
            'filters' => $filters,
            'records' => $records,
            'perPage' => $request->perPage(),
            'options' => [
                'provinces' => $this->mapSelectOptions(
                    Province::query()->orderBy('name')->get(['id', 'name'])
                ),
                'regencies' => $this->mapSelectOptions(
                    ($filters['province_id'] ?? null)
                        ? Regency::query()->where('province_id', $filters['province_id'])->orderBy('name')->get(['id', 'name'])
                        : collect()
                ),
                'districts' => $this->mapSelectOptions(
                    ($filters['regency_id'] ?? null)
                        ? District::query()->where('regency_id', $filters['regency_id'])->orderBy('name')->get(['id', 'name'])
                        : collect()
                ),
                'villages' => $this->mapSelectOptions(
                    ($filters['district_id'] ?? null)
                        ? Village::query()->where('district_id', $filters['district_id'])->orderBy('name')->get(['id', 'name'])
                        : collect()
                ),
                'jenisListings' => $this->mapSelectOptions(
                    JenisListing::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
                ),
                'jenisObjeks' => $this->mapSelectOptions(
                    JenisObjek::query()
                        ->where('is_active', true)
                        ->whereNotIn('slug', ['non-properti', 'non_properti', 'nonproperti', 'non_property', 'non-properties', 'non_properties'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%non properti%'])
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->get(['id', 'name'])
                ),
                'creators' => $this->creatorOptions(),
                'perPage' => collect([8, 16, 32, 64])->map(fn (int $value): array => [
                    'label' => "{$value} / halaman",
                    'value' => $value,
                ])->all(),
            ],
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): Response
    {
        Gate::authorize('create', Pembanding::class);

        return Inertia::render('Pembanding/Create', [
            'options' => $this->formOptions(),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(PembandingStoreRequest $request, SavePembandingAction $savePembanding): RedirectResponse
    {
        Gate::authorize('create', Pembanding::class);

        $data = $request->validated();
        $createAnother = $request->boolean('create_another');
        $data['created_by'] = $request->user()->id;

        $pembanding = $savePembanding->create($data, $request->file('image'));

        if ($createAnother) {
            return redirect()
                ->route('home.pembanding.create')
                ->with('success', 'Data pembanding berhasil ditambahkan. Silakan input data baru.');
        }

        return redirect()
            ->route('home.pembanding.show', $pembanding)
            ->with('success', 'Data pembanding berhasil ditambahkan.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(Pembanding $pembanding): Response
    {
        Gate::authorize('view', $pembanding);

        $pembanding->load([
            'jenisListing:id,name',
            'jenisObjek:id,name',
            'statusPemberiInformasi:id,name',
            'bentukTanah:id,name',
            'dokumenTanah:id,name',
            'posisiTanah:id,name',
            'kondisiTanah:id,name',
            'topografiRef:id,name',
            'peruntukanRef:id,name',
            'province:id,name',
            'regency:id,name',
            'district:id,name',
            'village:id,name',
            'creator:id,name,email',
            // FIX #2: Load relasi updater untuk field updated_by di Show page
            'updater:id,name,email',
        ]);

        $latestDeleteRequest = $pembanding->deleteRequests()
            ->with([
                'requestedBy:id,name',
                'reviewedBy:id,name',
            ])
            ->latest('id')
            ->first();

        $hasPendingDeleteRequest = $pembanding->deleteRequests()
            ->where('status', PembandingDeleteRequest::STATUS_PENDING)
            ->exists();

        return Inertia::render('Pembanding/Show', [
            'record' => [
                'id' => $pembanding->id,
                'alamat' => $pembanding->alamat_data,
                'nomer_telepon_pemberi_informasi' => $pembanding->nomer_telepon_pemberi_informasi,
                'nama_pemberi_informasi' => $pembanding->nama_pemberi_informasi,
                'harga' => $pembanding->harga,
                'is_sewa' => $pembanding->is_sewa,
                'jangka_waktu_sewa' => $pembanding->jangka_waktu_sewa,
                'satuan_waktu_sewa' => $pembanding->satuan_waktu_sewa,
                'sewa_periode_label' => $pembanding->sewa_periode_label,
                'tanggal' => $pembanding->tanggal_data,
                'jenis_listing' => $pembanding->jenisListing?->name,
                'jenis_objek' => $pembanding->jenisObjek?->name,
                'status_pemberi_informasi' => $pembanding->statusPemberiInformasi?->name,
                'bentuk_tanah' => $pembanding->bentukTanah?->name,
                'dokumen_tanah' => $pembanding->dokumenTanah?->name,
                'posisi_tanah' => $pembanding->posisiTanah?->name,
                'kondisi_tanah' => $pembanding->kondisiTanah?->name,
                'topografi' => $pembanding->topografiRef?->name,
                'peruntukan' => $pembanding->peruntukanRef?->name,
                'luas_tanah' => $pembanding->luas_tanah,
                'luas_bangunan' => $pembanding->luas_bangunan,
                'tahun_bangun' => $pembanding->tahun_bangun,
                'lebar_depan' => $pembanding->lebar_depan,
                'lebar_jalan' => $pembanding->lebar_jalan,
                'rasio_tapak' => $pembanding->rasio_tapak,
                'location' => collect([
                    $pembanding->village?->name,
                    $pembanding->district?->name,
                    $pembanding->regency?->name,
                    $pembanding->province?->name,
                ])->filter()->implode(', '),
                'province' => $pembanding->province?->name,
                'regency' => $pembanding->regency?->name,
                'district' => $pembanding->district?->name,
                'village' => $pembanding->village?->name,
                'latitude' => $pembanding->latitude,
                'longitude' => $pembanding->longitude,
                'image_url' => $pembanding->image_path,
                'catatan' => $pembanding->catatan,
                'created_by' => $pembanding->creator?->name,
                // FIX #2: Pass updated_by ke frontend
                'updated_by' => $pembanding->updater?->name,
                'created_at' => optional($pembanding->created_at)->toDateTimeString(),
                'updated_at' => optional($pembanding->updated_at)->toDateTimeString(),
                'has_pending_delete_request' => $hasPendingDeleteRequest,
                'delete_request' => $latestDeleteRequest ? [
                    'id' => $latestDeleteRequest->id,
                    'status' => $latestDeleteRequest->status,
                    'reason' => $latestDeleteRequest->reason,
                    'review_note' => $latestDeleteRequest->review_note,
                    'requested_by' => $latestDeleteRequest->requestedBy?->name,
                    'reviewed_by' => $latestDeleteRequest->reviewedBy?->name,
                    'requested_at' => optional($latestDeleteRequest->created_at)->toDateTimeString(),
                    'reviewed_at' => optional($latestDeleteRequest->reviewed_at)->toDateTimeString(),
                ] : null,
                'can_update' => request()->user()?->can('update', $pembanding) ?? false,
                'can_request_delete' => request()->user()?->can('delete', $pembanding) ?? false,
            ],
        ]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit(Pembanding $pembanding): Response
    {
        Gate::authorize('update', $pembanding);

        $pembanding->load([
            'jenisListing:id,name',
            'jenisObjek:id,name',
            'statusPemberiInformasi:id,name',
            'bentukTanah:id,name',
            'dokumenTanah:id,name',
            'posisiTanah:id,name',
            'kondisiTanah:id,name',
            'topografiRef:id,name',
            'peruntukanRef:id,name',
            'province:id,name',
            'regency:id,name',
            'district:id,name',
            'village:id,name',
        ]);

        $record = [
            'id' => $pembanding->id,
            'jenis_listing_id' => $pembanding->jenis_listing_id,
            'jenis_objek_id' => $pembanding->jenis_objek_id,
            'nama_pemberi_informasi' => $pembanding->nama_pemberi_informasi,
            'nomer_telepon_pemberi_informasi' => $pembanding->nomer_telepon_pemberi_informasi,
            'status_pemberi_informasi_id' => $pembanding->status_pemberi_informasi_id,
            'tanggal_data' => $pembanding->tanggal_data,
            'alamat_data' => $pembanding->alamat_data,
            'province_id' => $pembanding->province_id,
            'regency_id' => $pembanding->regency_id,
            'district_id' => $pembanding->district_id,
            'village_id' => $pembanding->village_id,
            'latitude' => $pembanding->latitude,
            'longitude' => $pembanding->longitude,
            'image_url' => $pembanding->image_path,
            'luas_tanah' => $pembanding->luas_tanah,
            'luas_bangunan' => $pembanding->luas_bangunan,
            'lebar_depan' => $pembanding->lebar_depan,
            'lebar_jalan' => $pembanding->lebar_jalan,
            'tahun_bangun' => $pembanding->tahun_bangun,
            'rasio_tapak' => $pembanding->rasio_tapak,
            'bentuk_tanah_id' => $pembanding->bentuk_tanah_id,
            'posisi_tanah_id' => $pembanding->posisi_tanah_id,
            'kondisi_tanah_id' => $pembanding->kondisi_tanah_id,
            'topografi_id' => $pembanding->topografi_id,
            'dokumen_tanah_id' => $pembanding->dokumen_tanah_id,
            'peruntukan_id' => $pembanding->peruntukan_id,
            'harga' => $pembanding->harga,
            'jangka_waktu_sewa' => $pembanding->jangka_waktu_sewa,
            'satuan_waktu_sewa' => $pembanding->satuan_waktu_sewa,
            'catatan' => $pembanding->catatan,
        ];

        return Inertia::render('Pembanding/Edit', [
            'record' => $record,
            // FIX #6: Gunakan null-check sebelum query regency/district/village
            // agar tidak query dengan kondisi WHERE x = NULL
            'options' => $this->formOptions($pembanding),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(
        PembandingUpdateRequest $request,
        Pembanding $pembanding,
        SavePembandingAction $savePembanding
    ): RedirectResponse {
        Gate::authorize('update', $pembanding);

        $data = $request->validated();

        $savePembanding->update($pembanding, $data, $request->file('image'));

        return redirect()
            ->route('home.pembanding.show', $pembanding)
            ->with('success', 'Data pembanding berhasil diperbarui.');
    }

    public function requestDelete(Request $request, Pembanding $pembanding): RedirectResponse
    {
        Gate::authorize('delete', $pembanding);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Alasan penghapusan wajib diisi.',
        ]);

        $alreadyPending = $pembanding->deleteRequests()
            ->where('status', PembandingDeleteRequest::STATUS_PENDING)
            ->exists();

        if ($alreadyPending) {
            return redirect()
                ->route('home.pembanding.show', $pembanding)
                ->with('error', 'Permintaan hapus sudah diajukan dan masih menunggu evaluasi super_admin.');
        }

        PembandingDeleteRequest::create([
            'pembanding_id' => $pembanding->id,
            'requested_by_id' => $request->user()->id,
            'reason' => trim($data['reason']),
            'status' => PembandingDeleteRequest::STATUS_PENDING,
        ]);

        return redirect()
            ->route('home.pembanding.show', $pembanding)
            ->with('success', 'Permintaan hapus berhasil dikirim dan menunggu evaluasi super_admin.');
    }

    // ── History ───────────────────────────────────────────────────────────────

    // FIX #7: Terima $request secara eksplisit — tidak lagi pakai global helper request()
    public function history(Pembanding $pembanding, Request $request)
    {
        Gate::authorize('view', $pembanding);

        $activities = $pembanding->activities()
            ->latest()
            ->with('causer:id,name,email')
            ->take(100)
            ->get()
            ->map(function ($activity) {
                $propertiesRaw = $activity->properties;

                if ($propertiesRaw instanceof Collection) {
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

                return [
                    'id' => $activity->id,
                    'event' => $activity->event ?? $activity->description,
                    'causer' => $activity->causer?->name ?? 'Sistem',
                    'causer_email' => $activity->causer?->email,
                    'created_at' => $activity->created_at?->toDateTimeString(),
                    'changes' => $changes,
                ];
            });

        // FIX #7: Gunakan $request yang diinjeksikan, bukan global helper
        if ($request->boolean('json')) {
            return response()->json(['data' => $activities]);
        }

        return Inertia::render('Pembanding/History', [
            'record' => [
                'id' => $pembanding->id,
                'alamat' => $pembanding->alamat_data,
                // FIX #1: Tambahkan location agar PembandingHistoryHeader bisa menampilkannya
                'location' => collect([
                    $pembanding->village?->name,
                    $pembanding->district?->name,
                    $pembanding->regency?->name,
                ])->filter()->implode(', '),
            ],
            'activities' => $activities,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * @param  Pembanding|null  $pembanding  Jika diisi, load regency/district/village sesuai record.
     */
    private function formOptions(?Pembanding $pembanding = null): array
    {
        return $this->formOptionsService->for($pembanding?->getAttributes() ?? []);
    }

    /**
     * FIX #9: Tambahkan type hint parameter dan return type yang eksplisit.
     */
    private function mapSelectOptions(Collection $items): array
    {
        return $items
            ->map(fn ($item): array => [
                'label' => (string) $item->name,
                'value' => $item->id,
            ])
            ->values()
            ->all();
    }

    private function creatorOptions(): array
    {
        return User::query()
            ->whereHas('pembanding')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'label' => $user->name,
                'value' => $user->id,
            ])
            ->values()
            ->all();
    }
}
