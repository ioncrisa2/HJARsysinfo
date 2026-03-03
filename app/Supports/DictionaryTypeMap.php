<?php

namespace App\Supports;

final class DictionaryTypeMap
{
    public const MAP = [
        'jenis-listing' => \App\Models\JenisListing::class,
        'jenis-objek' => \App\Models\JenisObjek::class,
        'status-pemberi-informasi' => \App\Models\StatusPemberiInformasi::class,
        'bentuk-tanah' => \App\Models\BentukTanah::class,
        'kondisi-tanah' => \App\Models\KondisiTanah::class,
        'posisi-tanah' => \App\Models\PosisiTanah::class,
        'topografi' => \App\Models\Topografi::class,
        'dokumen-tanah' => \App\Models\DokumenTanah::class,
        'peruntukan' => \App\Models\Peruntukan::class,
    ];

    public static function resolveModel(string $type): ?string
    {
        return self::MAP[$type] ?? null;
    }
}
