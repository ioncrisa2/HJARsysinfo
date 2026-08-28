<?php

namespace App\Console\Commands;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncDataWilayah extends Command
{
    protected $signature = 'app:sync-data-wilayah';
    protected $description = 'Sinkronisasi data wilayah Indonesia dari API ke database lokal';
    protected $baseUrl = 'https://emsifa.github.io/api-wilayah-indonesia/api';

    public function handle()
    {
        $this->info('Mulai sinkronisasi data wilayah...');

        // Provinsi adalah fondasi kategori lain, kalau ini gagal total ya stop di sini.
        try {
            $this->syncProvinces();
        } catch (\Throwable $e) {
            $this->error('Sinkronisasi provinsi gagal, proses dihentikan: ' . $e->getMessage());
            Log::error('Sync Wilayah Gagal (provinces): ' . $e->getMessage(), ['exception' => $e]);
            return 1;
        }

        // Kategori di bawah ini sudah handle error per-item di dalam masing-masing method,
        // try/catch di sini cuma jaga-jaga error tak terduga di level method itu sendiri
        // (misal koneksi DB putus total), supaya kategori setelahnya tetap dicoba.
        try {
            $this->syncRegencies();
        } catch (\Throwable $e) {
            $this->error('Sinkronisasi kabupaten/kota terhenti: ' . $e->getMessage());
            Log::error('Sync Wilayah Gagal (regencies): ' . $e->getMessage(), ['exception' => $e]);
        }

        try {
            $this->syncDistricts();
        } catch (\Throwable $e) {
            $this->error('Sinkronisasi kecamatan terhenti: ' . $e->getMessage());
            Log::error('Sync Wilayah Gagal (districts): ' . $e->getMessage(), ['exception' => $e]);
        }

        try {
            $this->syncVillages();
        } catch (\Throwable $e) {
            $this->error('Sinkronisasi desa/kelurahan terhenti: ' . $e->getMessage());
            Log::error('Sync Wilayah Gagal (villages): ' . $e->getMessage(), ['exception' => $e]);
        }

        $this->info('Sinkronisasi data wilayah selesai.');
        return 0;
    }

    private function syncProvinces()
    {
        $this->line('Menyinkronkan Provinsi...');
        $response = Http::retry(3, 100)->get("{$this->baseUrl}/provinces.json");

        if (!$response->ok()) {
            throw new \Exception('Gagal mengambil data provinsi.');
        }

        $provinces = $response->json();

        $dataToUpsert = collect($provinces)->map(fn ($data) => [
            'id' => $data['id'],
            'name' => $data['name']
        ])->all();

        // Cuma 1 kali upsert untuk semua provinsi, jadi 1 transaksi kecil saja cukup.
        DB::transaction(function () use ($dataToUpsert) {
            Province::upsert($dataToUpsert, ['id'], ['name']);
        });

        $this->info(count($dataToUpsert) . ' Provinsi disinkronkan.');
    }

    private function syncRegencies()
    {
        $this->line('Menyinkronkan Kabupaten/Kota...');

        $provinces = Province::lazy();
        $bar = $this->output->createProgressBar($provinces->count());
        $bar->start();

        $failedCount = 0;

        foreach ($provinces as $province) {
            try {
                $response = Http::retry(3, 100)->get("{$this->baseUrl}/regencies/{$province->id}.json");

                if (!$response->ok()) {
                    Log::warning("Gagal mengambil data kabupaten untuk provinsi: {$province->name}");
                    $failedCount++;
                    $bar->advance();
                    continue;
                }

                $regencies = $response->json();
                if (empty($regencies)) {
                    $bar->advance();
                    continue;
                }

                $dataToUpsert = collect($regencies)->map(fn ($data) => [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'province_id' => $province->id
                ])->all();

                // Transaksi per-provinsi: kalau gagal di provinsi ke-N,
                // provinsi 1..N-1 yang sudah sukses TIDAK ikut rollback.
                DB::transaction(function () use ($dataToUpsert) {
                    Regency::upsert($dataToUpsert, ['id'], ['name', 'province_id']);
                });
            } catch (\Throwable $e) {
                // Nangkep exception dari Http::retry() yang exhaust semua percobaan,
                // supaya 1 provinsi gagal gak ngebatalin seluruh proses sync kabupaten.
                Log::warning("Gagal sinkron kabupaten untuk provinsi {$province->name}: {$e->getMessage()}");
                $failedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($failedCount > 0) {
            $this->warn("{$failedCount} provinsi gagal disinkronkan datanya, cek log untuk detail. Jalankan ulang command untuk retry (aman, tidak duplikat).");
        }

        $this->newLine();
    }

    private function syncDistricts()
    {
        $this->line('Menyinkronkan Kecamatan...');

        $regencies = Regency::lazy();
        $bar = $this->output->createProgressBar($regencies->count());
        $bar->start();

        $failedCount = 0;

        foreach ($regencies as $regency) {
            try {
                $response = Http::retry(3, 100)->get("{$this->baseUrl}/districts/{$regency->id}.json");

                if (!$response->ok()) {
                    Log::warning("Gagal mengambil data kecamatan untuk kabupaten: {$regency->name}");
                    $failedCount++;
                    $bar->advance();
                    continue;
                }

                $districts = $response->json();
                if (empty($districts)) {
                    $bar->advance();
                    continue;
                }

                $dataToUpsert = collect($districts)->map(fn ($data) => [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'regency_id' => $regency->id
                ])->all();

                DB::transaction(function () use ($dataToUpsert) {
                    District::upsert($dataToUpsert, ['id'], ['name', 'regency_id']);
                });
            } catch (\Throwable $e) {
                Log::warning("Gagal sinkron kecamatan untuk kabupaten {$regency->name}: {$e->getMessage()}");
                $failedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($failedCount > 0) {
            $this->warn("{$failedCount} kabupaten/kota gagal disinkronkan datanya, cek log untuk detail. Jalankan ulang command untuk retry (aman, tidak duplikat).");
        }

        $this->newLine();
    }

    private function syncVillages()
    {
        $this->line('Menyinkronkan Desa/Kelurahan...');

        $districts = District::lazy();
        $bar = $this->output->createProgressBar($districts->count());
        $bar->start();

        $failedCount = 0;

        foreach ($districts as $district) {
            try {
                $response = Http::retry(3, 100)->get("{$this->baseUrl}/villages/{$district->id}.json");

                if (!$response->ok()) {
                    Log::warning("Gagal mengambil data desa untuk kecamatan: {$district->name}");
                    $failedCount++;
                    $bar->advance();
                    continue;
                }

                $villages = $response->json();
                if (empty($villages)) {
                    $bar->advance();
                    continue;
                }

                $dataToUpsert = collect($villages)->map(fn ($data) => [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'district_id' => $district->id
                ])->all();

                // 1 transaksi kecil per kecamatan
                DB::transaction(function () use ($dataToUpsert) {
                    Village::upsert($dataToUpsert, ['id'], ['name', 'district_id']);
                });
            } catch (\Throwable $e) {
                Log::warning("Gagal sinkron desa untuk kecamatan {$district->name}: {$e->getMessage()}");
                $failedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($failedCount > 0) {
            $this->warn("{$failedCount} kecamatan gagal disinkronkan datanya, cek log untuk detail. Jalankan ulang command untuk retry (aman, tidak duplikat).");
        }

        $this->newLine();
    }
}
