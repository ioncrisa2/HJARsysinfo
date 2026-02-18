<x-filament-panels::page>
@php
    $record = $this->record;
    $lat    = $record->latitude;
    $lng    = $record->longitude;
    $hasMap = $lat && $lng;

    // Helper: format rupiah
    $formatHarga = function($v) {
        $v = (float)($v ?? 0);
        if ($v >= 1_000_000_000) {
            $m = floor(($v / 1_000_000_000) * 100) / 100;
            return 'Rp ' . rtrim(rtrim(number_format($m, 2, '.', ''), '0'), '.') . ' M';
        }
        if ($v >= 1_000_000) {
            $j = $v / 1_000_000;
            if ($j >= 100) return 'Rp ' . (int)floor($j) . ' Juta';
            $j = floor($j * 10) / 10;
            return 'Rp ' . rtrim(rtrim(number_format($j, 1, '.', ''), '0'), '.') . ' Juta';
        }
        return 'Rp ' . number_format((int)$v, 0, ',', '.');
    };

    $formatLuas = fn($s) => is_numeric($s)
        ? ((float)$s >= 10000
            ? number_format((float)$s / 10000, 2, ',', '.') . ' ha'
            : number_format((float)$s, 0, ',', '.') . ' m²')
        : '-';

    $imageUrl = $record->image
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->image)
        : 'https://placehold.co/1200x400?text=No+Image';

    $fullAddress = collect([
        $record->alamat_data,
        $record->village?->name,
        $record->district?->name,
        $record->regency?->name,
        $record->province?->name,
    ])->filter()->implode(', ');
@endphp

{{-- ══════════════════════════════════════════════════════
     STYLES
══════════════════════════════════════════════════════ --}}
@push('styles')
<style>
    :root {
        --amber:       #f59e0b;
        --amber-light: #fef3c7;
        --amber-dark:  #d97706;
        --slate-50:    #f8fafc;
        --slate-100:   #f1f5f9;
        --slate-200:   #e2e8f0;
        --slate-500:   #64748b;
        --slate-700:   #334155;
        --slate-900:   #0f172a;
        --card-radius: 14px;
        --card-shadow: 0 1px 3px rgba(0,0,0,.06), 0 6px 20px rgba(0,0,0,.06);
    }

    .vdp-wrap { font-family: inherit; max-width: 100%; }

    /* ── Hero ──────────────────────────────────────────── */
    .vdp-hero {
        position: relative;
        width: 100%;
        height: 340px;
        border-radius: var(--card-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
        margin-bottom: 24px;
    }
    .vdp-hero img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
    }
    .vdp-hero-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(
            to top,
            rgba(10,15,30,.85) 0%,
            rgba(10,15,30,.3)  50%,
            transparent        100%
        );
    }
    .vdp-hero-content {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        padding: 28px 28px 24px;
    }
    .vdp-hero-chips {
        display: flex; gap: 8px; flex-wrap: wrap;
        margin-bottom: 10px;
    }
    .vdp-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px; font-weight: 700;
        letter-spacing: .3px;
    }
    .vdp-chip-amber  { background: var(--amber);       color: #fff; }
    .vdp-chip-blue   { background: #3b82f6;             color: #fff; }
    .vdp-chip-green  { background: #10b981;             color: #fff; }
    .vdp-chip-purple { background: #8b5cf6;             color: #fff; }
    .vdp-hero-title {
        font-size: 22px; font-weight: 800;
        color: #fff; line-height: 1.25;
        margin: 0 0 6px;
        text-shadow: 0 1px 4px rgba(0,0,0,.4);
    }
    .vdp-hero-address {
        font-size: 13px; color: rgba(255,255,255,.7);
        display: flex; align-items: center; gap: 5px;
    }
    .vdp-hero-address svg { width: 13px; height: 13px; flex-shrink: 0; }
    .vdp-hero-price {
        position: absolute;
        top: 20px; right: 24px;
        background: var(--amber);
        color: #fff;
        font-size: 18px; font-weight: 800;
        padding: 8px 18px;
        border-radius: 10px;
        box-shadow: 0 4px 16px rgba(245,158,11,.45);
        letter-spacing: -.3px;
    }

    /* ── Layout ────────────────────────────────────────── */
    .vdp-body {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 20px;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .vdp-body { grid-template-columns: 1fr; }
    }

    /* ── Card ──────────────────────────────────────────── */
    .vdp-card {
        background: #fff;
        border: 1px solid var(--slate-200);
        border-radius: var(--card-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
        margin-bottom: 16px;
    }
    .dark .vdp-card {
        background: #111827;
        border-color: rgba(255,255,255,.08);
    }
    .vdp-card:last-child { margin-bottom: 0; }

    /* ── Card header ───────────────────────────────────── */
    .vdp-card-header {
        display: flex; align-items: center; gap: 10px;
        padding: 13px 18px 11px;
        background: linear-gradient(135deg, var(--slate-50), var(--slate-100));
        border-bottom: 1px solid var(--slate-200);
    }
    .dark .vdp-card-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border-color: rgba(255,255,255,.06);
    }
    .vdp-card-header-icon {
        width: 28px; height: 28px;
        background: var(--amber);
        border-radius: 7px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vdp-card-header-icon svg { width: 14px; height: 14px; color: #fff; }
    .vdp-card-header h3 {
        font-size: 12px; font-weight: 800;
        text-transform: uppercase; letter-spacing: .6px;
        color: var(--slate-700); margin: 0;
    }
    .dark .vdp-card-header h3 { color: #cbd5e1; }

    /* ── Card body ─────────────────────────────────────── */
    .vdp-card-body { padding: 18px; }

    /* ── Data grid ─────────────────────────────────────── */
    .vdp-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px 20px;
    }
    .vdp-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 14px 20px;
    }
    .vdp-field {}
    .vdp-field-label {
        font-size: 10px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .6px;
        color: #94a3b8; margin-bottom: 3px;
    }
    .vdp-field-value {
        font-size: 13px; font-weight: 600;
        color: var(--slate-700);
    }
    .dark .vdp-field-value { color: #e2e8f0; }
    .vdp-field-value.accent { color: var(--amber-dark); font-size: 15px; font-weight: 800; }
    .vdp-field-value.muted  { color: #94a3b8; font-weight: 400; }

    /* ── Divider ───────────────────────────────────────── */
    .vdp-divider {
        height: 1px; background: var(--slate-200);
        margin: 16px 0;
    }
    .dark .vdp-divider { background: rgba(255,255,255,.06); }

    /* ── Badge ─────────────────────────────────────────── */
    .vdp-badge {
        display: inline-flex; align-items: center;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 11px; font-weight: 700;
        letter-spacing: .3px;
    }
    .vdp-badge-amber  { background: var(--amber-light); color: var(--amber-dark); }
    .vdp-badge-blue   { background: #dbeafe; color: #1d4ed8; }
    .vdp-badge-green  { background: #d1fae5; color: #065f46; }

    /* ── Price card special ────────────────────────────── */
    .vdp-price-block {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border: 1px solid #fde68a;
        border-radius: 10px;
        padding: 16px 18px;
        margin-bottom: 16px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .dark .vdp-price-block {
        background: rgba(245,158,11,.1);
        border-color: rgba(245,158,11,.2);
    }
    .vdp-price-block-amount {
        font-size: 26px; font-weight: 900;
        color: var(--amber-dark); letter-spacing: -.5px;
    }
    .vdp-price-block-label {
        font-size: 10px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .7px;
        color: #92400e; opacity: .7;
        margin-bottom: 2px;
    }
    .vdp-price-block-icon {
        width: 44px; height: 44px;
        background: var(--amber);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
    }
    .vdp-price-block-icon svg { width: 22px; height: 22px; color: #fff; }

    /* ── Contact row ───────────────────────────────────── */
    .vdp-contact-row {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px;
        background: var(--slate-50);
        border: 1px solid var(--slate-200);
        border-radius: 9px;
        margin-bottom: 8px;
    }
    .dark .vdp-contact-row {
        background: #1e293b;
        border-color: rgba(255,255,255,.06);
    }
    .vdp-contact-row:last-child { margin-bottom: 0; }
    .vdp-contact-icon {
        width: 32px; height: 32px;
        background: var(--amber-light);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vdp-contact-icon svg { width: 15px; height: 15px; color: var(--amber-dark); }
    .vdp-contact-label { font-size: 10px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
    .vdp-contact-value { font-size: 12.5px; font-weight: 600; color: var(--slate-700); }
    .dark .vdp-contact-value { color: #e2e8f0; }

    /* ── Leaflet map ───────────────────────────────────── */
    .vdp-map {
        width: 100%; height: 240px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid var(--slate-200);
    }
    .dark .vdp-map { border-color: rgba(255,255,255,.08); }

    /* ── Coordinates ───────────────────────────────────── */
    .vdp-coords {
        display: flex; gap: 8px; margin-top: 10px;
    }
    .vdp-coord-pill {
        flex: 1;
        background: var(--slate-50);
        border: 1px solid var(--slate-200);
        border-radius: 8px;
        padding: 7px 12px;
        text-align: center;
    }
    .dark .vdp-coord-pill {
        background: #1e293b;
        border-color: rgba(255,255,255,.06);
    }
    .vdp-coord-pill-label { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; }
    .vdp-coord-pill-val   { font-size: 12px; font-weight: 700; color: var(--slate-700); font-variant-numeric: tabular-nums; }
    .dark .vdp-coord-pill-val { color: #e2e8f0; }

    /* ── Catatan ───────────────────────────────────────── */
    .vdp-catatan {
        font-size: 13px; line-height: 1.65;
        color: var(--slate-500);
    }

    /* ── Maps CTA button ───────────────────────────────── */
    .vdp-map-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px;
        background: var(--amber);
        color: #fff;
        border-radius: 8px;
        font-size: 12px; font-weight: 700;
        text-decoration: none;
        transition: background .15s;
        margin-top: 10px;
    }
    .vdp-map-btn:hover { background: var(--amber-dark); color: #fff; }
    .vdp-map-btn svg { width: 13px; height: 13px; }

    /* ── Full span ─────────────────────────────────────── */
    .vdp-span-full { grid-column: 1 / -1; }
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

<div class="vdp-wrap">

    {{-- ════════════════════════════════════════════════
         HERO
    ════════════════════════════════════════════════ --}}
    <div class="vdp-hero">
        <img src="{{ $imageUrl }}" alt="Foto Properti">
        <div class="vdp-hero-overlay"></div>

        {{-- Price badge top-right --}}
        <div class="vdp-hero-price">{{ $formatHarga($record->harga) }}</div>

        <div class="vdp-hero-content">
            <div class="vdp-hero-chips">
                @if($record->jenisListing)
                    <span class="vdp-chip vdp-chip-amber">{{ $record->jenisListing->name }}</span>
                @endif
                @if($record->jenisObjek)
                    <span class="vdp-chip vdp-chip-blue">{{ $record->jenisObjek->name }}</span>
                @endif
                @if($record->dokumenTanah)
                    <span class="vdp-chip vdp-chip-green">{{ $record->dokumenTanah->name }}</span>
                @endif
            </div>
            <h1 class="vdp-hero-title">{{ strtoupper($record->alamat_singkat ?? $record->alamat_data) }}</h1>
            <div class="vdp-hero-address">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                </svg>
                {{ $fullAddress }}
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         BODY
    ════════════════════════════════════════════════ --}}
    <div class="vdp-body">

        {{-- ── LEFT COLUMN ────────────────────────────── --}}
        <div>

            {{-- SECTION: Lokasi & Peta --}}
            <div class="vdp-card">
                <div class="vdp-card-header">
                    <div class="vdp-card-header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                        </svg>
                    </div>
                    <h3>Lokasi</h3>
                </div>
                <div class="vdp-card-body">
                    <div class="vdp-field" style="margin-bottom:14px;">
                        <div class="vdp-field-label">Alamat Lengkap</div>
                        <div class="vdp-field-value">{{ $record->alamat_data }}</div>
                    </div>
                    <div class="vdp-grid" style="margin-bottom:16px;">
                        <div class="vdp-field">
                            <div class="vdp-field-label">Provinsi</div>
                            <div class="vdp-field-value">{{ $record->province?->name ?? '—' }}</div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Kabupaten / Kota</div>
                            <div class="vdp-field-value">{{ $record->regency?->name ?? '—' }}</div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Kecamatan</div>
                            <div class="vdp-field-value">{{ $record->district?->name ?? '—' }}</div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Desa / Kelurahan</div>
                            <div class="vdp-field-value">{{ $record->village?->name ?? '—' }}</div>
                        </div>
                    </div>

                    @if($hasMap)
                        {{-- Leaflet map --}}
                        <div id="vdp-map" class="vdp-map"></div>

                        <div class="vdp-coords">
                            <div class="vdp-coord-pill">
                                <div class="vdp-coord-pill-label">Latitude</div>
                                <div class="vdp-coord-pill-val">{{ $lat }}</div>
                            </div>
                            <div class="vdp-coord-pill">
                                <div class="vdp-coord-pill-label">Longitude</div>
                                <div class="vdp-coord-pill-val">{{ $lng }}</div>
                            </div>
                        </div>

                        <a href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}"
                           target="_blank" class="vdp-map-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                            </svg>
                            Buka di Google Maps
                        </a>
                    @else
                        <div style="padding:16px;text-align:center;color:#94a3b8;font-size:13px;background:#f8fafc;border-radius:10px;border:1px dashed #e2e8f0;">
                            Koordinat lokasi belum tersedia
                        </div>
                    @endif
                </div>
            </div>

            {{-- SECTION: Detail Fisik --}}
            <div class="vdp-card">
                <div class="vdp-card-header">
                    <div class="vdp-card-header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/>
                        </svg>
                    </div>
                    <h3>Detail Fisik Properti</h3>
                </div>
                <div class="vdp-card-body">
                    <div class="vdp-grid-3">
                        <div class="vdp-field">
                            <div class="vdp-field-label">Luas Tanah</div>
                            <div class="vdp-field-value accent">{{ $formatLuas($record->luas_tanah) }}</div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Luas Bangunan</div>
                            <div class="vdp-field-value">
                                @if($record->luas_bangunan && $record->luas_bangunan != 0)
                                    {{ $formatLuas($record->luas_bangunan) }}
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Tahun Bangun</div>
                            <div class="vdp-field-value">{{ $record->tahun_bangun ?? '—' }}</div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Lebar Depan</div>
                            <div class="vdp-field-value">{{ $record->lebar_depan ? $record->lebar_depan . ' m' : '—' }}</div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Lebar Jalan</div>
                            <div class="vdp-field-value">{{ $record->lebar_jalan ? $record->lebar_jalan . ' m' : '—' }}</div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Rasio Tapak</div>
                            <div class="vdp-field-value">{{ $record->rasio_tapak ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="vdp-divider"></div>

                    <div class="vdp-grid">
                        <div class="vdp-field">
                            <div class="vdp-field-label">Bentuk Tanah</div>
                            <div class="vdp-field-value">{{ $record->bentukTanah?->name ?? '—' }}</div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Posisi Letak</div>
                            <div class="vdp-field-value">{{ $record->posisiTanah?->name ?? '—' }}</div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Kondisi Lahan</div>
                            <div class="vdp-field-value">{{ $record->kondisiTanah?->name ?? '—' }}</div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Topografi</div>
                            <div class="vdp-field-value">{{ $record->topografiRef?->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION: Legalitas & Peruntukan --}}
            <div class="vdp-card">
                <div class="vdp-card-header">
                    <div class="vdp-card-header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                    </div>
                    <h3>Legalitas & Peruntukan</h3>
                </div>
                <div class="vdp-card-body">
                    <div class="vdp-grid">
                        <div class="vdp-field">
                            <div class="vdp-field-label">Dokumen Tanah</div>
                            <div class="vdp-field-value">
                                @if($record->dokumenTanah)
                                    <span class="vdp-badge vdp-badge-green">{{ $record->dokumenTanah->name }}</span>
                                @else —
                                @endif
                            </div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Peruntukan Lahan</div>
                            <div class="vdp-field-value">
                                @if($record->peruntukanRef)
                                    <span class="vdp-badge vdp-badge-blue">{{ $record->peruntukanRef->name }}</span>
                                @else —
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end LEFT --}}

        {{-- ── RIGHT COLUMN ───────────────────────────── --}}
        <div>

            {{-- SECTION: Harga & Info --}}
            <div class="vdp-card">
                <div class="vdp-card-header">
                    <div class="vdp-card-header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                        </svg>
                    </div>
                    <h3>Harga & Informasi</h3>
                </div>
                <div class="vdp-card-body">

                    {{-- Price block --}}
                    <div class="vdp-price-block">
                        <div>
                            <div class="vdp-price-block-label">Estimasi Harga</div>
                            <div class="vdp-price-block-amount">{{ $formatHarga($record->harga) }}</div>
                        </div>
                        <div class="vdp-price-block-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Listing type + date --}}
                    <div class="vdp-grid" style="margin-bottom:16px;">
                        <div class="vdp-field">
                            <div class="vdp-field-label">Jenis Listing</div>
                            <div class="vdp-field-value">
                                @if($record->jenisListing)
                                    <span class="vdp-badge vdp-badge-amber">{{ $record->jenisListing->name }}</span>
                                @else —
                                @endif
                            </div>
                        </div>
                        <div class="vdp-field">
                            <div class="vdp-field-label">Tanggal Data</div>
                            <div class="vdp-field-value">
                                {{ $record->tanggal_data ? \Carbon\Carbon::parse($record->tanggal_data)->isoFormat('D MMM YYYY') : '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="vdp-divider"></div>

                    {{-- Pemberi informasi --}}
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:10px;">
                        Pemberi Informasi
                    </div>

                    <div class="vdp-contact-row">
                        <div class="vdp-contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="vdp-contact-label">Nama</div>
                            <div class="vdp-contact-value">{{ $record->nama_pemberi_informasi ?? $record->nama_pemberi_info ?? '—' }}</div>
                        </div>
                    </div>

                    @if($record->nomer_telepon_pemberi_informasi ?? $record->nomer_info)
                    <div class="vdp-contact-row">
                        <div class="vdp-contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="vdp-contact-label">Telepon</div>
                            <div class="vdp-contact-value">{{ $record->nomer_telepon_pemberi_informasi ?? $record->nomer_info }}</div>
                        </div>
                    </div>
                    @endif

                    @if($record->statusPemberiInformasi)
                    <div class="vdp-contact-row">
                        <div class="vdp-contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="vdp-contact-label">Status</div>
                            <div class="vdp-contact-value">{{ $record->statusPemberiInformasi->name }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- SECTION: Catatan --}}
            @if($record->catatan)
            <div class="vdp-card">
                <div class="vdp-card-header">
                    <div class="vdp-card-header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                        </svg>
                    </div>
                    <h3>Catatan Tambahan</h3>
                </div>
                <div class="vdp-card-body">
                    <p class="vdp-catatan">{{ $record->catatan }}</p>
                </div>
            </div>
            @endif

        </div>{{-- end RIGHT --}}

    </div>{{-- end BODY --}}
</div>

{{-- ════════════════════════════════════════════════
     LEAFLET MAP INIT
════════════════════════════════════════════════ --}}
@if($hasMap)
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lat = {{ $lat }};
        const lng = {{ $lng }};

        const map = L.map('vdp-map', {
            center: [lat, lng],
            zoom: 16,
            zoomControl: true,
            scrollWheelZoom: false,
            attributionControl: false,
        });

        // Satellite tiles (same key used in your existing map picker)
        L.tileLayer(
            'https://api.maptiler.com/tiles/satellite-v2/{z}/{x}/{y}.jpg?key=tsNmu1udsggoxQXYTlrP',
            { maxZoom: 20 }
        ).addTo(map);

        // Custom amber marker
        const icon = L.divIcon({
            html: `<div style="
                width:32px;height:32px;
                background:#f59e0b;
                border:3px solid #fff;
                border-radius:50% 50% 50% 0;
                transform:rotate(-45deg);
                box-shadow:0 3px 10px rgba(0,0,0,.3);
            "></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            className: '',
        });

        const marker = L.marker([lat, lng], { icon }).addTo(map);
        marker.bindPopup(`
            <div style="font-family:inherit;padding:4px 2px;">
                <div style="font-weight:700;font-size:13px;margin-bottom:3px;">
                    {{ addslashes($record->alamat_singkat ?? $record->alamat_data) }}
                </div>
                <div style="font-size:12px;color:#64748b;">
                    {{ addslashes(collect([$record->district?->name, $record->regency?->name])->filter()->implode(', ')) }}
                </div>
            </div>
        `);
    });
</script>
@endpush
@endif

</x-filament-panels::page>
