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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-data-wilayah';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data wilayah Indonesia dari API ke database lokal';

    /**
    * Based url for sync data from api
    *
    */
    protected $baseUrl = 'https://emsifa.github.io/api-wilayah-indonesia/api';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mulai sinkronisasi data wilayah...');
        // 1. Sinkronisasi Provinsi
        $this->syncProvinces();

        // 2. Sinkronisasi Kabupaten/Kota
        $this->syncRegencies();

        // 3. Sinkronisasi Kecamatan
        $this->syncDistricts();

        // 4. Sinkronisasi Desa/Kelurahan
        $this->syncVillages();

        $this->info('Sinkronisasi data wilayah selesai.');
        return 0;
    }

    private function syncProvinces()
    {
        $this->line('Menyinkronkan Provinsi...');
        $response = Http::get("{$this->baseUrl}/provinces.json");

        if (!$response->ok()) {
            $this->error('Gagal mengambil data provinsi.');
            return;
        }

        $provinces = $response->json();
        $bar = $this->output->createProgressBar(count($provinces));
        $bar->start();

        foreach ($provinces as $data) {
            Province::updateOrCreate(
                ['id' => $data['id']],
                ['name' => $data['name']]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function syncRegencies()
    {
        $this->line('Menyinkronkan Kabupaten/Kota...');
        $provinces = Province::all();
        $bar = $this->output->createProgressBar($provinces->count());
        $bar->start();

        foreach ($provinces as $province) {
            $response = Http::get("{$this->baseUrl}/regencies/{$province->id}.json");

            if (!$response->ok()) {
                Log::warning("Gagal mengambil data kabupaten untuk provinsi: {$province->name}");
                continue;
            }

            $regencies = $response->json();
            foreach ($regencies as $data) {
                Regency::updateOrCreate(
                    ['id' => $data['id']],
                    ['name' => $data['name'], 'province_id' => $province->id]
                );
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function syncDistricts()
    {
        $this->line('Menyinkronkan Kecamatan...');
        $regencies = Regency::all();
        $bar = $this->output->createProgressBar($regencies->count());
        $bar->start();

        foreach ($regencies as $regency) {
            $response = Http::get("{$this->baseUrl}/districts/{$regency->id}.json");

            if (!$response->ok()) {
                Log::warning("Gagal mengambil data kecamatan untuk kabupaten: {$regency->name}");
                continue;
            }

            $districts = $response->json();
            foreach ($districts as $data) {
                District::updateOrCreate(
                    ['id' => $data['id']],
                    ['name' => $data['name'], 'regency_id' => $regency->id]
                );
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function syncVillages()
    {
        $this->line('Menyinkronkan Desa/Kelurahan...');
        $districts = District::all();
        $bar = $this->output->createProgressBar($districts->count());
        $bar->start();

        foreach ($districts as $district) {
            $response = Http::get("{$this->baseUrl}/villages/{$district->id}.json");

            if (!$response->ok()) {
                Log::warning("Gagal mengambil data desa untuk kecamatan: {$district->name}");
                continue;
            }

            $villages = $response->json();
            foreach ($villages as $data) {
                Village::updateOrCreate(
                    ['id' => $data['id']],
                    ['name' => $data['name'], 'district_id' => $district->id]
                );
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }
}
