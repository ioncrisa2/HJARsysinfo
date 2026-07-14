<?php

namespace App\Services\Exports;

use App\Exports\PembandingQueryExport;
use App\Exports\PembandingSelectionExport;
use App\Models\Pembanding;
use App\Support\Exports\PembandingExportColumnRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PembandingExportFileService
{
    public function __construct(private readonly PembandingExportColumnRegistry $registry) {}

    /**
     * @param  array<int, string>  $columns
     * @param  array<string, mixed>  $metadata
     */
    public function download(Collection $records, string $format, string $mode, array $columns, array $metadata): BinaryFileResponse|StreamedResponse
    {
        $filename = 'data-pembanding-'.now()->format('Ymd_His');

        return match ($format) {
            'pdf' => $this->pdf($records, $mode, $columns, $metadata, $filename),
            'csv' => $this->csv($records, $columns, $filename),
            'geojson' => $this->geoJson($records, $columns, $filename),
            'kml' => $this->kml($records, $columns, $filename),
            default => Excel::download(
                new PembandingSelectionExport($records, $columns, $metadata, $this->registry),
                "{$filename}.xlsx",
            ),
        };
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<string, mixed>  $metadata
     * @return array{path: string, filename: string, checksum: string}
     */
    public function store(Collection $records, string $format, string $mode, array $columns, array $metadata, string $disk, string $directory): array
    {
        $base = 'data-pembanding-'.now()->format('Ymd_His');
        $extension = $format === 'excel' ? 'xlsx' : $format;
        $filename = "{$base}.{$extension}";
        $path = trim($directory, '/').'/'.$filename;

        match ($format) {
            'excel' => Excel::store(new PembandingSelectionExport($records, $columns, $metadata, $this->registry), $path, $disk),
            'pdf' => Storage::disk($disk)->put($path, $this->pdfContents($records, $mode, $columns, $metadata)),
            'csv' => Storage::disk($disk)->put($path, $this->csvContents($records, $columns)),
            'geojson' => Storage::disk($disk)->put($path, $this->geoJsonContents($records, $columns)),
            'kml' => Storage::disk($disk)->put($path, $this->kmlContents($records, $columns)),
            default => throw new \InvalidArgumentException("Format export {$format} tidak didukung."),
        };

        return [
            'path' => $path,
            'filename' => $filename,
            'checksum' => hash_file('sha256', Storage::disk($disk)->path($path)),
        ];
    }

    /**
     * Store an asynchronous export without loading the complete dataset for stream-friendly formats.
     *
     * @param  array<int, string>  $columns
     * @param  array<string, mixed>  $metadata
     * @return array{path: string, filename: string, checksum: string}
     */
    public function storeQuery(Builder $query, string $format, string $mode, array $columns, array $metadata, string $disk, string $directory): array
    {
        $base = 'data-pembanding-'.now()->format('Ymd_His');
        $extension = $format === 'excel' ? 'xlsx' : $format;
        $filename = "{$base}.{$extension}";
        $path = trim($directory, '/').'/'.$filename;

        if ($format === 'excel') {
            Excel::store(new PembandingQueryExport(clone $query, $columns, $metadata, $this->registry), $path, $disk);
        } elseif ($format === 'pdf') {
            Storage::disk($disk)->put($path, $this->pdfContents((clone $query)->get(), $mode, $columns, $metadata));
        } else {
            Storage::disk($disk)->makeDirectory(dirname($path));
            $target = fopen(Storage::disk($disk)->path($path), 'wb');
            match ($format) {
                'csv' => $this->writeCsvQuery($target, $query, $columns),
                'geojson' => $this->writeGeoJsonQuery($target, $query, $columns),
                'kml' => $this->writeKmlQuery($target, $query, $columns),
                default => throw new \InvalidArgumentException("Format export {$format} tidak didukung."),
            };
            fclose($target);
        }

        return [
            'path' => $path,
            'filename' => $filename,
            'checksum' => hash_file('sha256', Storage::disk($disk)->path($path)),
        ];
    }

    private function writeCsvQuery($target, Builder $query, array $columns): void
    {
        fwrite($target, "\xEF\xBB\xBF");
        fputcsv($target, $this->registry->headings($columns));
        (clone $query)->chunk(500, function ($records) use ($target, $columns): void {
            foreach ($records as $record) {
                fputcsv($target, $this->registry->map($record, $columns, true));
            }
        });
    }

    private function writeGeoJsonQuery($target, Builder $query, array $columns): void
    {
        fwrite($target, '{"type":"FeatureCollection","features":[');
        $first = true;
        (clone $query)->chunk(500, function ($records) use ($target, $columns, &$first): void {
            foreach ($records as $record) {
                if (! is_numeric($record->latitude) || ! is_numeric($record->longitude)) {
                    continue;
                }
                $feature = [
                    'type' => 'Feature',
                    'id' => $record->id,
                    'geometry' => ['type' => 'Point', 'coordinates' => [(float) $record->longitude, (float) $record->latitude]],
                    'properties' => collect($columns)
                        ->reject(fn (string $column): bool => in_array($column, ['latitude', 'longitude'], true))
                        ->mapWithKeys(fn (string $column): array => [$column => $this->registry->value($record, $column)])->all(),
                ];
                fwrite($target, ($first ? '' : ',').json_encode($feature, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
                $first = false;
            }
        });
        fwrite($target, ']}');
    }

    private function writeKmlQuery($target, Builder $query, array $columns): void
    {
        fwrite($target, '<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://www.opengis.net/kml/2.2"><Document>');
        (clone $query)->chunk(500, function ($records) use ($target, $columns): void {
            foreach ($records as $record) {
                if (! is_numeric($record->latitude) || ! is_numeric($record->longitude)) {
                    continue;
                }
                $properties = collect($columns)
                    ->reject(fn (string $column): bool => in_array($column, ['latitude', 'longitude'], true))
                    ->map(fn (string $column): string => $this->registry->headings([$column])[0].': '.($this->registry->value($record, $column) ?? '-'))->implode("\n");
                fwrite($target, '<Placemark><name>'.$this->xml($record->alamat_data ?: "Data #{$record->id}").'</name>');
                fwrite($target, '<description>'.$this->xml($properties).'</description>');
                fwrite($target, '<Point><coordinates>'.(float) $record->longitude.','.(float) $record->latitude.',0</coordinates></Point></Placemark>');
            }
        });
        fwrite($target, '</Document></kml>');
    }

    private function pdf(Collection $records, string $mode, array $columns, array $metadata, string $filename): StreamedResponse
    {
        $view = $mode === 'detail' ? 'exports.pembanding-pdf' : 'exports.pembanding-summary-pdf';
        $pdf = Pdf::loadView($view, [
            'records' => $records,
            'columns' => $columns,
            'registry' => $this->registry,
            'metadata' => $metadata,
            'includeSensitive' => in_array('nama_pemberi_informasi', $columns, true)
                || in_array('nomor_telepon', $columns, true),
        ])->setPaper('a4', 'landscape');

        return Response::streamDownload(
            fn () => print $pdf->output(),
            "{$filename}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function pdfContents(Collection $records, string $mode, array $columns, array $metadata): string
    {
        $view = $mode === 'detail' ? 'exports.pembanding-pdf' : 'exports.pembanding-summary-pdf';

        return Pdf::loadView($view, [
            'records' => $records,
            'columns' => $columns,
            'registry' => $this->registry,
            'metadata' => $metadata,
            'includeSensitive' => in_array('nama_pemberi_informasi', $columns, true)
                || in_array('nomor_telepon', $columns, true),
        ])->setPaper('a4', 'landscape')->output();
    }

    private function csv(Collection $records, array $columns, string $filename): StreamedResponse
    {
        return Response::streamDownload(function () use ($records, $columns): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $this->registry->headings($columns));
            foreach ($records as $record) {
                fputcsv($stream, $this->registry->map($record, $columns, true));
            }
            fclose($stream);
        }, "{$filename}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvContents(Collection $records, array $columns): string
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $this->registry->headings($columns));
        foreach ($records as $record) {
            fputcsv($stream, $this->registry->map($record, $columns, true));
        }
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return $contents;
    }

    private function geoJson(Collection $records, array $columns, string $filename): StreamedResponse
    {
        return Response::streamDownload(function () use ($records, $columns): void {
            echo json_encode([
                'type' => 'FeatureCollection',
                'features' => $records
                    ->filter(fn (Pembanding $record): bool => is_numeric($record->latitude) && is_numeric($record->longitude))
                    ->map(fn (Pembanding $record): array => [
                        'type' => 'Feature',
                        'id' => $record->id,
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [(float) $record->longitude, (float) $record->latitude],
                        ],
                        'properties' => collect($columns)
                            ->reject(fn (string $column): bool => in_array($column, ['latitude', 'longitude'], true))
                            ->mapWithKeys(fn (string $column): array => [$column => $this->registry->value($record, $column)])
                            ->all(),
                    ])->values()->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }, "{$filename}.geojson", ['Content-Type' => 'application/geo+json; charset=UTF-8']);
    }

    private function geoJsonContents(Collection $records, array $columns): string
    {
        return json_encode([
            'type' => 'FeatureCollection',
            'features' => $records
                ->filter(fn (Pembanding $record): bool => is_numeric($record->latitude) && is_numeric($record->longitude))
                ->map(fn (Pembanding $record): array => [
                    'type' => 'Feature',
                    'id' => $record->id,
                    'geometry' => ['type' => 'Point', 'coordinates' => [(float) $record->longitude, (float) $record->latitude]],
                    'properties' => collect($columns)
                        ->reject(fn (string $column): bool => in_array($column, ['latitude', 'longitude'], true))
                        ->mapWithKeys(fn (string $column): array => [$column => $this->registry->value($record, $column)])
                        ->all(),
                ])->values()->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function kml(Collection $records, array $columns, string $filename): StreamedResponse
    {
        return Response::streamDownload(function () use ($records, $columns): void {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<kml xmlns="http://www.opengis.net/kml/2.2"><Document>';
            foreach ($records as $record) {
                if (! is_numeric($record->latitude) || ! is_numeric($record->longitude)) {
                    continue;
                }
                $properties = collect($columns)
                    ->reject(fn (string $column): bool => in_array($column, ['latitude', 'longitude'], true))
                    ->map(fn (string $column): string => $this->registry->headings([$column])[0].': '.($this->registry->value($record, $column) ?? '-'))
                    ->implode("\n");
                echo '<Placemark><name>'.$this->xml($record->alamat_data ?: "Data #{$record->id}").'</name>';
                echo '<description>'.$this->xml($properties).'</description>';
                echo '<Point><coordinates>'.(float) $record->longitude.','.(float) $record->latitude.',0</coordinates></Point></Placemark>';
            }
            echo '</Document></kml>';
        }, "{$filename}.kml", ['Content-Type' => 'application/vnd.google-earth.kml+xml; charset=UTF-8']);
    }

    private function kmlContents(Collection $records, array $columns): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://www.opengis.net/kml/2.2"><Document>';
        foreach ($records as $record) {
            if (! is_numeric($record->latitude) || ! is_numeric($record->longitude)) {
                continue;
            }
            $properties = collect($columns)
                ->reject(fn (string $column): bool => in_array($column, ['latitude', 'longitude'], true))
                ->map(fn (string $column): string => $this->registry->headings([$column])[0].': '.($this->registry->value($record, $column) ?? '-'))
                ->implode("\n");
            $xml .= '<Placemark><name>'.$this->xml($record->alamat_data ?: "Data #{$record->id}").'</name>';
            $xml .= '<description>'.$this->xml($properties).'</description>';
            $xml .= '<Point><coordinates>'.(float) $record->longitude.','.(float) $record->latitude.',0</coordinates></Point></Placemark>';
        }

        return $xml.'</Document></kml>';
    }

    private function xml(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
