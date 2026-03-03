<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataPageController extends Controller
{
    /**
        * Render unified Master Data page for dictionaries & location hierarchy.
        */
    public function __invoke(): Response
    {
        return Inertia::render('MasterData/Index', [
            'dictionaries' => [
                ['type' => 'jenis-listing', 'label' => 'Jenis Listing', 'icon' => 'pi-tag', 'extra' => ['badge_color_token', 'marker_icon_url']],
                ['type' => 'jenis-objek', 'label' => 'Jenis Objek', 'icon' => 'pi-building'],
                ['type' => 'status-pemberi-informasi', 'label' => 'Status Pemberi Info', 'icon' => 'pi-user'],
                ['type' => 'bentuk-tanah', 'label' => 'Bentuk Tanah', 'icon' => 'pi-map'],
                ['type' => 'kondisi-tanah', 'label' => 'Kondisi Tanah', 'icon' => 'pi-sparkles'],
                ['type' => 'posisi-tanah', 'label' => 'Posisi Tanah', 'icon' => 'pi-arrows-h'],
                ['type' => 'topografi', 'label' => 'Topografi', 'icon' => 'pi-chart-line'],
                ['type' => 'dokumen-tanah', 'label' => 'Dokumen Tanah', 'icon' => 'pi-file'],
                ['type' => 'peruntukan', 'label' => 'Peruntukan', 'icon' => 'pi-flag'],
            ],
            'locationMeta' => [
                ['level' => 'province', 'label' => 'Provinsi'],
                ['level' => 'regency', 'label' => 'Kabupaten / Kota'],
                ['level' => 'district', 'label' => 'Kecamatan'],
                ['level' => 'village', 'label' => 'Desa / Kelurahan'],
            ],
        ]);
    }
}
