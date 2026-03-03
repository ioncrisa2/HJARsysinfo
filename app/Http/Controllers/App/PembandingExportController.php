<?php

namespace App\Http\Controllers\App;

use App\Exports\PembandingSelectionExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\PembandingBrowseRequest;
use App\Models\Pembanding;
use App\Services\Pembanding\PembandingBrowseFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

class PembandingExportController extends Controller
{
    private const MAX_ROWS = 5000;

    public function byFilter(PembandingBrowseRequest $request, PembandingBrowseFilterService $filterService)
    {
        $format = $request->get('format', 'excel');
        $filters = $request->filters($filterService);

        $query = $filterService
            ->apply($this->baseQuery(), $filters)
            ->orderByDesc('tanggal_data')
            ->orderByDesc('id')
            ->limit(self::MAX_ROWS);

        $records = $query->get();

        return $this->export($records, $format);
    }

    private function baseQuery()
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

    private function export(Collection $records, string $format)
    {
        $filename = 'pembanding-' . now()->format('Ymd_His');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.pembanding-pdf', [
                'records' => $records,
            ])->setPaper('a4', 'landscape');

            return Response::streamDownload(
                fn() => print($pdf->output()),
                "{$filename}.pdf",
                ['Content-Type' => 'application/pdf']
            );
        }

        // default Excel
        return Excel::download(new PembandingSelectionExport($records), "{$filename}.xlsx");
    }
}
