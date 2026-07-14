<?php

namespace App\Supports;

use App\Models\BentukTanah;
use App\Models\DokumenTanah;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\KondisiTanah;
use App\Models\Peruntukan;
use App\Models\PosisiTanah;
use App\Models\StatusPemberiInformasi;
use App\Models\Topografi;

final class DictionaryTypeMap
{
    public const DEFINITIONS = [
        'jenis-listing' => [
            'model' => JenisListing::class,
            'label' => 'Jenis Listing',
            'icon' => 'pi-tag',
            'description' => 'Kategori penawaran atau transaksi properti.',
            'extra' => ['badge_color', 'marker_icon_url'],
        ],
        'jenis-objek' => [
            'model' => JenisObjek::class,
            'label' => 'Jenis Objek',
            'icon' => 'pi-building',
            'description' => 'Jenis properti yang dicatat sebagai data pembanding.',
            'extra' => [],
        ],
        'status-pemberi-informasi' => [
            'model' => StatusPemberiInformasi::class,
            'label' => 'Status Pemberi Informasi',
            'icon' => 'pi-user',
            'description' => 'Hubungan pemberi informasi dengan objek properti.',
            'extra' => [],
        ],
        'bentuk-tanah' => [
            'model' => BentukTanah::class,
            'label' => 'Bentuk Tanah',
            'icon' => 'pi-map',
            'description' => 'Klasifikasi bentuk bidang tanah.',
            'extra' => [],
        ],
        'kondisi-tanah' => [
            'model' => KondisiTanah::class,
            'label' => 'Kondisi Tanah',
            'icon' => 'pi-th-large',
            'description' => 'Kondisi fisik tanah pada saat pendataan.',
            'extra' => [],
        ],
        'posisi-tanah' => [
            'model' => PosisiTanah::class,
            'label' => 'Posisi Tanah',
            'icon' => 'pi-arrows-h',
            'description' => 'Posisi bidang tanah terhadap jalan atau lingkungan.',
            'extra' => [],
        ],
        'topografi' => [
            'model' => Topografi::class,
            'label' => 'Topografi',
            'icon' => 'pi-chart-line',
            'description' => 'Kondisi kemiringan dan kontur permukaan tanah.',
            'extra' => [],
        ],
        'dokumen-tanah' => [
            'model' => DokumenTanah::class,
            'label' => 'Dokumen Tanah',
            'icon' => 'pi-file',
            'description' => 'Jenis bukti kepemilikan atau penguasaan tanah.',
            'extra' => [],
        ],
        'peruntukan' => [
            'model' => Peruntukan::class,
            'label' => 'Peruntukan',
            'icon' => 'pi-flag',
            'description' => 'Pemanfaatan atau peruntukan utama properti.',
            'extra' => [],
        ],
    ];

    public static function resolveModel(string $type): ?string
    {
        return self::DEFINITIONS[$type]['model'] ?? null;
    }

    public static function resolve(string $type): ?array
    {
        if (! isset(self::DEFINITIONS[$type])) {
            return null;
        }

        return ['type' => $type, ...self::DEFINITIONS[$type]];
    }

    public static function definitions(): array
    {
        return collect(self::DEFINITIONS)
            ->map(fn (array $definition, string $type): array => ['type' => $type, ...$definition])
            ->values()
            ->all();
    }

    public static function publicDefinitions(): array
    {
        return collect(self::definitions())
            ->map(fn (array $definition): array => collect($definition)->except('model')->all())
            ->all();
    }
}
