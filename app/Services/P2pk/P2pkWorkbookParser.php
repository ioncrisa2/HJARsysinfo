<?php

namespace App\Services\P2pk;

use App\Exceptions\InvalidP2pkWorkbookException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class P2pkWorkbookParser
{
    public const SHEET_NAME = 'Data_Pembanding';

    public const MAX_ROWS = 500;

    public const HEADERS = [
        'Nomor Laporan Penilaian', 'Jenis Pembanding', 'Alamat', 'RT/RW', 'Desa',
        'Kecamatan', 'Kota', 'Propinsi', 'Koordinat', 'Luas Tanah', 'Luas Bangunan',
        'Indikasi Nilai', 'Transaksi Penawaran', 'Harga', 'Bulan Tahun', 'Sumber Data',
        'Sumber Data Lainnya', 'Kontak Sumber Data',
    ];

    public function __construct(private readonly P2pkValueNormalizer $normalizer) {}

    /** @return array{sheet_name: string, rows: array<int, array{row_number: int, values: array<string, mixed>}>} */
    public function parse(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $sheetInfo = collect($reader->listWorksheetInfo($path))
                ->firstWhere('worksheetName', self::SHEET_NAME);
            if (! $sheetInfo) {
                throw new InvalidP2pkWorkbookException('Sheet Data_Pembanding tidak ditemukan.');
            }
            if (($sheetInfo['totalRows'] ?? 0) - 1 > self::MAX_ROWS) {
                throw new InvalidP2pkWorkbookException('File berisi lebih dari '.self::MAX_ROWS.' data. Pecah file menjadi beberapa unggahan.');
            }
            $spreadsheet = $reader->load($path);
        } catch (InvalidP2pkWorkbookException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new InvalidP2pkWorkbookException('File Excel tidak dapat dibaca. Pastikan file tidak rusak.', previous: $exception);
        }

        try {
            $sheet = $spreadsheet->getSheetByName(self::SHEET_NAME);
            if (! $sheet) {
                throw new InvalidP2pkWorkbookException('Sheet Data_Pembanding tidak ditemukan.');
            }

            $this->validateHeaders($sheet);
            $highestRow = $sheet->getHighestDataRow();
            if ($highestRow - 1 > self::MAX_ROWS) {
                throw new InvalidP2pkWorkbookException('File berisi lebih dari '.self::MAX_ROWS.' data. Pecah file menjadi beberapa unggahan.');
            }

            $rows = [];
            for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
                $values = $sheet->rangeToArray("A{$rowNumber}:R{$rowNumber}", null, false, true, false)[0];
                if (collect($values)->every(fn (mixed $value): bool => $this->normalizer->text($value) === null)) {
                    continue;
                }

                $rows[] = [
                    'row_number' => $rowNumber,
                    'values' => array_combine(self::HEADERS, $values),
                ];
            }

            if ($rows === []) {
                throw new InvalidP2pkWorkbookException('Sheet Data_Pembanding tidak memiliki data.');
            }

            return ['sheet_name' => self::SHEET_NAME, 'rows' => $rows];
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function validateHeaders(Worksheet $sheet): void
    {
        $headers = $sheet->rangeToArray('A1:R1', null, false, true, false)[0];
        $headers = array_map(fn (mixed $value): ?string => $this->normalizer->text($value), $headers);

        if ($headers !== self::HEADERS) {
            throw new InvalidP2pkWorkbookException('Susunan kolom Excel tidak sesuai format P2PK yang didukung.');
        }
    }
}
