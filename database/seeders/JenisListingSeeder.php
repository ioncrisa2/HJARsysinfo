<?php

namespace Database\Seeders;

use App\Models\JenisListing;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class JenisListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $rows = [
            ['slug' => 'penawaran', 'name' => 'Penawaran'],
            ['slug' => 'transaksi', 'name' => 'Transaksi'],
            ['slug' => 'sewa', 'name' => 'Sewa'],
        ];

        foreach ($rows as $row) {
            JenisListing::query()->updateOrCreate(
                ['slug' => $row['slug']],
                ['name' => $row['name']],
            );
        }
    }
}
