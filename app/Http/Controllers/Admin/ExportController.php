<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PembandingSelectionExport;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\Pembanding;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private const MAX_EXPORT_ROWS = 5000;

    public function index(Request $request): InertiaResponse
    {
        $filters = $this->filters($request);
        $query = $this->applyFilters($this->baseQuery(), $filters);

        $records = (clone $query)
            ->orderByDesc('tanggal_data')
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->through(fn (Pembanding $record): array => $this->mapRecord($record))
            ->withQueryString();

        return Inertia::render('Admin/Export/Index', [
            'records' => $records,
            'filters' => $filters,
            'options' => $this->options($filters),
            'summary' => [
                'total' => (clone $query)->count(),
                'max_export_rows' => self::MAX_EXPORT_ROWS,
            ],
        ]);
    }

    public function download(Request $request): BinaryFileResponse|StreamedResponse
    {
        $data = $request->validate([
            'format' => ['required', 'string', 'in:excel,pdf'],
            'ids' => ['nullable', 'string'],
            'province_id' => ['nullable', 'string', 'exists:provinces,id'],
            'regency_id' => ['nullable', 'string', 'exists:regencies,id'],
            'district_id' => ['nullable', 'string', 'exists:districts,id'],
            'village_id' => ['nullable', 'string', 'exists:villages,id'],
            'jenis_listing_id' => ['nullable', 'integer', 'exists:master_jenis_listing,id'],
            'jenis_objek_id' => ['nullable', 'integer', 'exists:master_jenis_objek,id'],
            'dari_tanggal' => ['nullable', 'date'],
            'sampai_tanggal' => ['nullable', 'date', 'after_or_equal:dari_tanggal'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $ids = $this->parseIds((string) ($data['ids'] ?? ''));
        $filters = $this->filters($request);

        $query = $this->applyFilters($this->baseQuery(), $filters);

        if ($ids !== []) {
            $query->whereKey($ids);
        } else {
            $query->limit(self::MAX_EXPORT_ROWS);
        }

        $records = $query
            ->orderByDesc('tanggal_data')
            ->orderByDesc('id')
            ->get();

        return $this->export($records, $data['format']);
    }

    private function baseQuery(): Builder
    {
        return Pembanding::query()->with([
            'province',
            'regency',
            'district',
            'village',
            'jenisListing',
            'jenisObjek',
            'statusPemberiInformasi',
            'bentukTanah',
            'dokumenTanah',
            'posisiTanah',
            'kondisiTanah',
            'topografiRef',
            'peruntukanRef',
        ]);
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['province_id'], fn (Builder $builder, string $value) => $builder->where('province_id', $value))
            ->when($filters['regency_id'], fn (Builder $builder, string $value) => $builder->where('regency_id', $value))
            ->when($filters['district_id'], fn (Builder $builder, string $value) => $builder->where('district_id', $value))
            ->when($filters['village_id'], fn (Builder $builder, string $value) => $builder->where('village_id', $value))
            ->when($filters['jenis_listing_id'], fn (Builder $builder, int $value) => $builder->where('jenis_listing_id', $value))
            ->when($filters['jenis_objek_id'], fn (Builder $builder, int $value) => $builder->where('jenis_objek_id', $value))
            ->when($filters['dari_tanggal'], fn (Builder $builder, string $date) => $builder->whereDate('tanggal_data', '>=', $date))
            ->when($filters['sampai_tanggal'], fn (Builder $builder, string $date) => $builder->whereDate('tanggal_data', '<=', $date))
            ->when($filters['q'], function (Builder $builder, string $search): void {
                $builder->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('alamat_data', 'like', "%{$search}%")
                        ->orWhere('nama_pemberi_informasi', 'like', "%{$search}%");
                });
            });
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'province_id' => ['nullable', 'string', 'exists:provinces,id'],
            'regency_id' => ['nullable', 'string', 'exists:regencies,id'],
            'district_id' => ['nullable', 'string', 'exists:districts,id'],
            'village_id' => ['nullable', 'string', 'exists:villages,id'],
            'jenis_listing_id' => ['nullable', 'integer', 'exists:master_jenis_listing,id'],
            'jenis_objek_id' => ['nullable', 'integer', 'exists:master_jenis_objek,id'],
            'dari_tanggal' => ['nullable', 'date'],
            'sampai_tanggal' => ['nullable', 'date', 'after_or_equal:dari_tanggal'],
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
        ]);

        $filters = [
            'province_id' => $validated['province_id'] ?? null,
            'regency_id' => $validated['regency_id'] ?? null,
            'district_id' => $validated['district_id'] ?? null,
            'village_id' => $validated['village_id'] ?? null,
            'jenis_listing_id' => isset($validated['jenis_listing_id']) ? (int) $validated['jenis_listing_id'] : null,
            'jenis_objek_id' => isset($validated['jenis_objek_id']) ? (int) $validated['jenis_objek_id'] : null,
            'dari_tanggal' => $validated['dari_tanggal'] ?? null,
            'sampai_tanggal' => $validated['sampai_tanggal'] ?? null,
            'q' => filled($validated['q'] ?? null) ? trim((string) $validated['q']) : null,
            'per_page' => (int) ($validated['per_page'] ?? 25),
        ];

        if (! $filters['province_id']) {
            $filters['regency_id'] = null;
            $filters['district_id'] = null;
            $filters['village_id'] = null;
        }

        if (! $filters['regency_id']) {
            $filters['district_id'] = null;
            $filters['village_id'] = null;
        }

        if (! $filters['district_id']) {
            $filters['village_id'] = null;
        }

        return $filters;
    }

    private function options(array $filters): array
    {
        return [
            'provinces' => $this->mapSelectOptions(Province::query()->orderBy('name')->get()),
            'regencies' => $filters['province_id']
                ? $this->mapSelectOptions(Regency::query()->where('province_id', $filters['province_id'])->orderBy('name')->get())
                : [],
            'districts' => $filters['regency_id']
                ? $this->mapSelectOptions(District::query()->where('regency_id', $filters['regency_id'])->orderBy('name')->get())
                : [],
            'villages' => $filters['district_id']
                ? $this->mapSelectOptions(Village::query()->where('district_id', $filters['district_id'])->orderBy('name')->get())
                : [],
            'jenisListings' => $this->mapSelectOptions(
                JenisListing::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            ),
            'jenisObjeks' => $this->mapSelectOptions(
                JenisObjek::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            ),
        ];
    }

    private function mapRecord(Pembanding $record): array
    {
        return [
            'id' => $record->id,
            'nama_pemberi_informasi' => $record->nama_pemberi_informasi,
            'alamat_data' => $record->alamat_data,
            'harga' => $record->harga,
            'tanggal_data' => $record->tanggal_data,
            'luas_tanah' => $record->luas_tanah,
            'luas_bangunan' => $record->luas_bangunan,
            'image_url' => $record->image ? Storage::disk('public')->url($record->image) : null,
            'jenis_listing' => $record->jenisListing?->name,
            'jenis_objek' => $record->jenisObjek?->name,
            'province' => $record->province?->name,
            'regency' => $record->regency?->name,
            'district' => $record->district?->name,
            'village' => $record->village?->name,
        ];
    }

    private function export(Collection $records, string $format): BinaryFileResponse|StreamedResponse
    {
        $filename = 'pembanding-' . now()->format('Ymd_His');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.pembanding-pdf', [
                'records' => $records,
            ])->setPaper('a4', 'landscape');

            return Response::streamDownload(
                fn () => print($pdf->output()),
                "{$filename}.pdf",
                ['Content-Type' => 'application/pdf']
            );
        }

        return Excel::download(new PembandingSelectionExport($records), "{$filename}.xlsx");
    }

    private function parseIds(string $ids): array
    {
        if ($ids === '') {
            return [];
        }

        return collect(explode(',', $ids))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

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
}
