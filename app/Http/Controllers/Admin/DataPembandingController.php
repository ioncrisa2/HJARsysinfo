<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\PembandingBrowseRequest;
use App\Http\Requests\App\PembandingStoreRequest;
use App\Http\Requests\App\PembandingUpdateRequest;
use App\Models\BentukTanah;
use App\Models\District;
use App\Models\DokumenTanah;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\KondisiTanah;
use App\Models\Pembanding;
use App\Models\Peruntukan;
use App\Models\PosisiTanah;
use App\Models\Province;
use App\Models\Regency;
use App\Models\StatusPemberiInformasi;
use App\Models\Topografi;
use App\Models\Village;
use App\Services\Pembanding\PembandingBrowseFilterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DataPembandingController extends Controller
{
    public function index(PembandingBrowseRequest $request, PembandingBrowseFilterService $filterService): Response
    {
        $filters = $request->filters($filterService);

        $records = $filterService
            ->apply(Pembanding::query(), $filters)
            ->with([
                'jenisListing:id,name,badge_color',
                'jenisObjek:id,name',
                'village:id,name,district_id',
                'district:id,name,regency_id',
                'regency:id,name',
            ])
            ->orderByDesc('tanggal_data')
            ->orderByDesc('id')
            ->paginate($request->perPage())
            ->through(fn(Pembanding $record): array => [
                'id'           => $record->id,
                'alamat_data'  => $record->alamat_data,
                'harga'        => $record->harga,
                'tanggal_data' => $record->tanggal_data,
                'luas_tanah'   => $record->luas_tanah,
                'luas_bangunan' => $record->luas_bangunan,
                'image_url'    => $record->image 
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->image) 
                    : null,
                'latitude'     => $record->latitude,
                'longitude'    => $record->longitude,
                'jenis_listing' => [
                    'name' => $record->jenisListing?->name,
                    'color' => $record->jenisListing?->badge_color,
                ],
                'jenis_objek'   => $record->jenisObjek?->name,
                'location'     => collect([
                    $record->village?->name,
                    $record->district?->name,
                    $record->regency?->name,
                ])->filter()->implode(', '),
            ])
            ->withQueryString();

        $options = [
            'provinces' => $this->mapSelectOptions(Province::query()->orderBy('name')->get()),
            'regencies' => $filters['province_id'] 
                ? $this->mapSelectOptions(Regency::where('province_id', $filters['province_id'])->orderBy('name')->get())
                : [],
            'districts' => $filters['regency_id'] 
                ? $this->mapSelectOptions(District::where('regency_id', $filters['regency_id'])->orderBy('name')->get())
                : [],
            'villages' => $filters['district_id'] 
                ? $this->mapSelectOptions(Village::where('district_id', $filters['district_id'])->orderBy('name')->get())
                : [],
            'jenisListings' => $this->mapSelectOptions(JenisListing::where('is_active', true)->orderBy('sort_order')->get()),
            'jenisObjeks' => $this->mapSelectOptions(JenisObjek::where('is_active', true)->orderBy('sort_order')->get()),
        ];

        return Inertia::render('Admin/Pembanding/Index', [
            'filters' => $filters,
            'records' => $records,
            'perPage' => (int) $request->perPage(),
            'options' => $options,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pembanding/Create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(PembandingStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $createAnother = $request->boolean('create_another');
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'));
        }

        Pembanding::create($data);

        return redirect()
            ->route('admin.pembanding.index')
            ->with('success', 'Data pembanding berhasil ditambahkan.');
    }

    public function show(Pembanding $pembanding): Response
    {
        $pembanding->load([
            'jenisListing:id,name,badge_color',
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
            'creator:id,name',
        ]);

        $pembanding->image_url = $pembanding->image 
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($pembanding->image) 
            : null;

        return Inertia::render('Admin/Pembanding/Show', [
            'record' => $pembanding,
        ]);
    }

    public function edit(Pembanding $pembanding): Response
    {
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

        // Explicitly set image_url for the frontend
        $pembanding->image_url = $pembanding->image 
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($pembanding->image) 
            : null;

        return Inertia::render('Admin/Pembanding/Edit', [
            'record'  => $pembanding,
            'options' => $this->formOptions($pembanding),
        ]);
    }

    public function update(PembandingUpdateRequest $request, Pembanding $pembanding): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'));
        } else {
            unset($data['image']);
        }

        $pembanding->update($data);

        return redirect()
            ->route('admin.pembanding.index')
            ->with('success', 'Data pembanding berhasil diperbarui.');
    }

    public function destroy(Pembanding $pembanding)
    {
        $pembanding->forceFill([
            'deleted_by_id' => auth()->id(),
            'deleted_reason' => 'Deleted by Super Admin via Admin Panel',
        ])->save();

        $pembanding->delete();

        return redirect()->back()->with('success', 'Property successfully deleted.');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function formOptions(?Pembanding $pembanding = null): array
    {
        return [
            'provinces'        => $this->mapSelectOptions(Province::query()->orderBy('name')->get()),
            'regencies'        => $this->mapSelectOptions(
                $pembanding?->province_id
                    ? Regency::query()->where('province_id', $pembanding->province_id)->orderBy('name')->get()
                    : collect()
            ),
            'districts'        => $this->mapSelectOptions(
                $pembanding?->regency_id
                    ? District::query()->where('regency_id', $pembanding->regency_id)->orderBy('name')->get()
                    : collect()
            ),
            'villages'         => $this->mapSelectOptions(
                $pembanding?->district_id
                    ? Village::query()->where('district_id', $pembanding->district_id)->orderBy('name')->get()
                    : collect()
            ),
            'jenisListings'    => $this->mapSelectOptions(JenisListing::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()),
            'jenisObjeks'      => $this->mapSelectOptions(
                JenisObjek::query()
                    ->where('is_active', true)
                    ->whereNotIn('slug', ['non-properti', 'non_properti', 'nonproperti', 'non_property', 'non-properties', 'non_properties'])
                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%non properti%'])
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get()
            ),
            'statusPemberiInfos' => $this->mapSelectOptions(StatusPemberiInformasi::query()->orderBy('name')->get()),
            'bentukTanahs'     => $this->mapSelectOptions(BentukTanah::query()->orderBy('name')->get()),
            'posisiTanahs'     => $this->mapSelectOptions(PosisiTanah::query()->orderBy('name')->get()),
            'kondisiTanahs'    => $this->mapSelectOptions(KondisiTanah::query()->orderBy('name')->get()),
            'topografis'       => $this->mapSelectOptions(Topografi::query()->orderBy('name')->get()),
            'dokumenTanahs'    => $this->mapSelectOptions(DokumenTanah::query()->orderBy('name')->get()),
            'peruntukans'      => $this->mapSelectOptions(Peruntukan::query()->orderBy('name')->get()),
            'tanahId'          => once(fn() => JenisObjek::query()->where('slug', 'tanah')->value('id')),
        ];
    }

    private function storeImage(\Illuminate\Http\UploadedFile $file): string
    {
        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('foto_pembanding', strtolower($filename), 'public');
    }

    private function mapSelectOptions(\Illuminate\Support\Collection $items): array
    {
        return $items
            ->map(fn($item): array => [
                'label' => (string) $item->name,
                'value' => $item->id,
            ])
            ->values()
            ->all();
    }
}
