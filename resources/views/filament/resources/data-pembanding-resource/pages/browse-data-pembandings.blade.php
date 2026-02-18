<x-filament-panels::page>
    <style>
        /* ═══════════════════════════════════════════════════
           SHARED DESIGN TOKENS
        ═══════════════════════════════════════════════════ */
        :root {
            --accent:        #f59e0b;
            --accent-light:  #fef3c7;
            --accent-dark:   #d97706;
            --card-bg:       #ffffff;
            --card-border:   #e5e7eb;
            --card-radius:   14px;
            --card-shadow:   0 1px 3px rgba(0,0,0,.06), 0 4px 12px rgba(0,0,0,.04);
            --header-bg:     linear-gradient(135deg, #f8fafc, #f1f5f9);
            --divider:       #f1f5f9;
            --text-primary:  #1e293b;
            --text-muted:    #64748b;
            --text-faint:    #94a3b8;
        }
        .dark {
            --card-bg:      #111827;
            --card-border:  rgba(255,255,255,.08);
            --header-bg:    linear-gradient(135deg, #1e293b, #0f172a);
            --divider:      rgba(255,255,255,.05);
            --text-primary: #f1f5f9;
            --text-muted:   #94a3b8;
            --text-faint:   #64748b;
        }

        .page-layout { font-family: inherit; }

        /* Ensure Select dropdown portals float above everything */
        .fi-select-options-dropdown,
        [data-headlessui-state] ul,
        .choices__list--dropdown,
        [x-ref="panel"],
        [x-placement] {
            z-index: 9999 !important;
        }

        /* ═══════════════════════════════════════════════════
           SHARED CARD SHELL
        ═══════════════════════════════════════════════════ */
        .fs-card, .ft-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--card-radius);
            /* No overflow:hidden here — it clips Select dropdowns */
            box-shadow: var(--card-shadow);
        }

        /* Clip only the top corners on the header */
        .fs-card > .fc-header:first-child,
        .ft-card > .fc-header:first-child {
            border-radius: var(--card-radius) var(--card-radius) 0 0;
        }

        /* Clip only the bottom corners on the footer */
        .fs-card > .fs-footer:last-child,
        .ft-card > .fs-footer:last-child {
            border-radius: 0 0 var(--card-radius) var(--card-radius);
        }

        /* ═══════════════════════════════════════════════════
           SHARED CARD HEADER (used by filter + table)
        ═══════════════════════════════════════════════════ */
        .fc-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px 12px;
            background: var(--header-bg);
            border-bottom: 1px solid var(--divider);
        }
        .fc-header-icon {
            width: 30px; height: 30px;
            background: var(--accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .fc-header-icon svg { width: 15px; height: 15px; color: #fff; }
        .fc-header h2 {
            font-size: 12.5px; font-weight: 700;
            color: var(--text-primary); letter-spacing: .2px; margin: 0;
        }
        .fc-header p { font-size: 10.5px; color: var(--text-faint); margin: 1px 0 0; }

        /* header right slot */
        .fc-header-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }

        /* ═══════════════════════════════════════════════════
           FILTER SIDEBAR
        ═══════════════════════════════════════════════════ */
        .fs-group {
            padding: 13px 15px;
            border-bottom: 1px solid var(--divider);
        }
        .fs-group-title {
            display: flex; align-items: center; gap: 5px;
            margin-bottom: 10px;
        }
        .fs-group-title svg { width: 11px; height: 11px; color: var(--accent); flex-shrink: 0; }
        .fs-group-title span {
            font-size: 9.5px; font-weight: 800;
            text-transform: uppercase; letter-spacing: .8px;
            color: var(--text-faint);
        }
        .fs-group-title::after { content: ''; flex: 1; height: 1px; background: var(--divider); }
        .fs-label {
            display: block; font-size: 11px; font-weight: 600;
            color: var(--text-muted); margin-bottom: 4px;
        }
        .fs-field { margin-bottom: 8px; }
        .fs-field:last-child { margin-bottom: 0; }
        .fs-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .fs-footer {
            padding: 11px 15px;
            background: var(--header-bg);
            border-top: 1px solid var(--divider);
        }
        .fs-btn-reset {
            width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            padding: 7px 12px;
            font-size: 11.5px; font-weight: 600;
            color: var(--text-muted);
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            cursor: pointer;
            transition: all .15s ease;
        }
        .fs-btn-reset:hover { color: #ef4444; border-color: #fca5a5; background: #fef2f2; }
        .fs-btn-reset svg { width: 12px; height: 12px; }

        /* Kill Filament-generated labels / section chrome inside sidebar */
        .page-layout .fi-fo-field-wrp-label-ctn,
        .page-layout label.fi-fo-field-wrp-label,
        .page-layout .fi-fo-field-wrp-helper-txt { display: none !important; }
        .page-layout .fi-section-header { display: none !important; }
        .page-layout .fi-section {
            background: transparent !important;
            border: none !important; box-shadow: none !important;
            padding: 0 !important; margin: 0 !important;
        }
        .page-layout .fi-section-content { padding: 0 !important; }
        .page-layout .fi-fo-field-wrp { margin-bottom: 0 !important; }
        .page-layout .fi-select-input,
        .page-layout .fi-input {
            font-size: 12.5px !important;
            padding-top: 6px !important; padding-bottom: 6px !important;
            border-radius: 8px !important; min-height: unset !important;
        }
        .page-layout .fi-fo-date-picker input {
            font-size: 12.5px !important;
            padding-top: 6px !important; padding-bottom: 6px !important;
            border-radius: 8px !important;
        }

        /* ═══════════════════════════════════════════════════
           TABLE PANEL — strip Filament's own card chrome
        ═══════════════════════════════════════════════════ */
        .ft-card .fi-ta {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }

        /* ── Table search bar — hidden (search is in sidebar) ── */
        .ft-card .fi-ta-search-field-wrp,
        .ft-card .fi-ta-search,
        .ft-card .fi-ta-header-toolbar,
        .ft-card input[type="search"],
        .ft-card .fi-input-wrp:has(input[type="search"]),
        .ft-card .fi-fo-field-wrp:has(input[type="search"]) {
            display: none !important;
        }

        /* ── Column header row ────────────────────────────── */
        .ft-card .fi-ta-header-cell {
            background: #f8fafc !important;
            border-bottom: 2px solid var(--divider) !important;
            padding-top: 10px !important;
            padding-bottom: 10px !important;
        }
        .dark .ft-card .fi-ta-header-cell {
            background: #1e293b !important;
            border-bottom-color: rgba(255,255,255,.07) !important;
        }
        .ft-card .fi-ta-header-cell-label {
            font-size: 10.5px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: .7px !important;
            color: var(--text-muted) !important;
        }

        /* ── Table rows ───────────────────────────────────── */
        .ft-card .fi-ta-row {
            border-bottom: 1px solid #f8fafc !important;
            transition: background .12s ease !important;
        }
        .dark .ft-card .fi-ta-row {
            border-bottom-color: rgba(255,255,255,.04) !important;
        }
        .ft-card .fi-ta-row:hover {
            background: #fffbeb !important;
        }
        .dark .ft-card .fi-ta-row:hover {
            background: rgba(245,158,11,.06) !important;
        }
        .ft-card .fi-ta-row:last-child {
            border-bottom: none !important;
        }

        /* ── Cell base ────────────────────────────────────── */
        .ft-card .fi-ta-cell {
            padding-top: 11px !important;
            padding-bottom: 11px !important;
            font-size: 12.5px !important;
        }

        /* ── Image cell ───────────────────────────────────── */
        .ft-card .fi-ta-col-image img,
        .ft-card img.fi-ta-image {
            border-radius: 8px !important;
            object-fit: cover !important;
            border: 2px solid var(--divider) !important;
        }

        /* ── Harga (price) column — amber weight ──────────── */
        .ft-card .fi-ta-col-harga .fi-ta-cell-text {
            font-weight: 700 !important;
            color: var(--accent-dark) !important;
        }

        /* ── Alamat column ────────────────────────────────── */
        .ft-card .fi-ta-col-alamat_singkat .fi-ta-cell-text {
            font-weight: 600 !important;
            color: var(--text-primary) !important;
        }
        .ft-card .fi-ta-col-alamat_singkat .fi-ta-cell-description {
            font-size: 11px !important;
            color: var(--text-faint) !important;
        }

        /* ── Badges ───────────────────────────────────────── */
        .ft-card .fi-badge {
            font-size: 10.5px !important;
            font-weight: 700 !important;
            border-radius: 6px !important;
            padding: 2px 8px !important;
            letter-spacing: .3px !important;
        }

        /* ── Tanggal column ───────────────────────────────── */
        .ft-card .fi-ta-col-tanggal_data .fi-ta-cell-text {
            font-size: 11.5px !important;
            color: var(--text-faint) !important;
        }

        /* ── Actions ──────────────────────────────────────── */
        .ft-card .fi-ta-actions {
            gap: 4px !important;
        }
        .ft-card .fi-ta-actions .fi-btn {
            font-size: 11px !important;
            padding: 4px 10px !important;
            border-radius: 7px !important;
            font-weight: 600 !important;
        }

        /* ── Pagination ───────────────────────────────────── */
        .ft-card .fi-pagination {
            padding: 12px 18px !important;
            border-top: 1px solid var(--divider) !important;
            background: var(--header-bg) !important;
        }
        .ft-card .fi-pagination-item-btn {
            font-size: 12px !important;
            border-radius: 7px !important;
            min-width: 30px !important;
            height: 30px !important;
        }
        .ft-card .fi-pagination-item-btn[aria-current="page"] {
            background: var(--accent) !important;
            border-color: var(--accent) !important;
            color: #fff !important;
            font-weight: 700 !important;
        }
        .ft-card .fi-pagination-item-btn:hover:not([aria-current="page"]) {
            background: var(--accent-light) !important;
            border-color: var(--accent) !important;
            color: var(--accent-dark) !important;
        }

        /* ── Sticky actions column ───────────────────────── */
        /* Last header cell (actions) */
        .ft-card .fi-ta-header-cell:last-child {
            position: sticky !important;
            right: 0 !important;
            z-index: 3 !important;
            background: #f8fafc !important;
            box-shadow: -3px 0 8px rgba(0,0,0,.06) !important;
        }
        .dark .ft-card .fi-ta-header-cell:last-child {
            background: #1e293b !important;
        }

        /* Last body cell (actions) in every row */
        .ft-card .fi-ta-row td:last-child {
            position: sticky !important;
            right: 0 !important;
            z-index: 2 !important;
            background: var(--card-bg) !important;
            box-shadow: -3px 0 8px rgba(0,0,0,.05) !important;
        }
        .ft-card .fi-ta-row:hover td:last-child {
            background: #fffbeb !important;
        }
        .dark .ft-card .fi-ta-row:hover td:last-child {
            background: rgba(245,158,11,.06) !important;
        }

        /* Style the ActionGroup button */
        .ft-card .fi-ta-row td:last-child .fi-btn {
            border-radius: 8px !important;
            padding: 4px 8px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            color: var(--text-muted) !important;
            background: var(--card-bg) !important;
            border: 1px solid var(--card-border) !important;
            transition: all .15s ease !important;
        }
        .ft-card .fi-ta-row td:last-child .fi-btn:hover {
            color: var(--accent-dark) !important;
            border-color: var(--accent) !important;
            background: var(--accent-light) !important;
        }

        /* ── Empty state ──────────────────────────────────── */
        .ft-card .fi-ta-empty-state-icon {
            color: var(--accent) !important;
            opacity: .4;
        }
        .ft-card .fi-ta-empty-state-heading {
            font-size: 14px !important;
            font-weight: 700 !important;
            color: var(--text-primary) !important;
        }

        /* ── Toolbar (search row) ─────────────────────────── */
        .ft-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 18px;
            border-bottom: 1px solid var(--divider);
            background: var(--card-bg);
        }
        .ft-toolbar-left { display: flex; align-items: center; gap: 8px; flex: 1; }

        /* kill Filament's own header toolbar so we can replace it */
        .ft-card .fi-ta-header {
            display: none !important;
        }

        /* count chip */
        .ft-count-chip {
            display: inline-flex; align-items: center;
            padding: 3px 10px;
            background: var(--accent-light);
            color: var(--accent-dark);
            border-radius: 20px;
            font-size: 11px; font-weight: 700;
            white-space: nowrap;
        }
    </style>

    <div class="flex gap-6 items-start page-layout">

        {{-- ═══════════════════════════════════════════
             FILTER SIDEBAR
        ═══════════════════════════════════════════ --}}
        <aside class="w-64 shrink-0 sticky top-4 self-start">
            <div class="fs-card">

                <div class="fc-header">
                    <div class="fc-header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
                        </svg>
                    </div>
                    <div>
                        <h2>Filter Data</h2>
                        <p>Saring data pembanding</p>
                    </div>
                </div>

                @php
                    $sectionComponents = $this->form->getComponents(withHidden: true);
                    $fields = collect($sectionComponents[0]->getChildComponents())->keyBy(fn($c) => $c->getName());
                @endphp

                {{-- GROUP 1: Lokasi --}}
                <div class="fs-group">
                    <div class="fs-group-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                        </svg>
                        <span>Lokasi</span>
                    </div>
                    <div class="fs-field">
                        <label class="fs-label">Provinsi</label>
                        {{ $fields->get('province_id') }}
                    </div>
                    @if(filled($this->filters['province_id'] ?? null))
                        <div class="fs-field">
                            <label class="fs-label">Kabupaten / Kota</label>
                            {{ $fields->get('regency_id') }}
                        </div>
                    @endif
                    @if(filled($this->filters['regency_id'] ?? null))
                        <div class="fs-field">
                            <label class="fs-label">Kecamatan</label>
                            {{ $fields->get('district_id') }}
                        </div>
                    @endif
                    @if(filled($this->filters['district_id'] ?? null))
                        <div class="fs-field">
                            <label class="fs-label">Desa / Kelurahan</label>
                            {{ $fields->get('village_id') }}
                        </div>
                    @endif
                </div>

                {{-- GROUP 2: Cari Alamat --}}
                <div class="fs-group">
                    <div class="fs-group-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                        <span>Cari Alamat</span>
                    </div>
                    <label class="fs-label">Nama Jalan</label>
                    {{ $fields->get('q') }}
                </div>

                {{-- GROUP 3: Rentang Tanggal --}}
                <div class="fs-group">
                    <div class="fs-group-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                        </svg>
                        <span>Rentang Tanggal</span>
                    </div>
                    <div class="fs-grid-2">
                        <div>
                            <label class="fs-label">Dari</label>
                            {{ $fields->get('dari_tanggal') }}
                        </div>
                        <div>
                            <label class="fs-label">Sampai</label>
                            {{ $fields->get('sampai_tanggal') }}
                        </div>
                    </div>
                </div>

                {{-- GROUP 4: Tipe Properti --}}
                <div class="fs-group">
                    <div class="fs-group-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>
                        </svg>
                        <span>Tipe Properti</span>
                    </div>
                    <div class="fs-grid-2">
                        <div>
                            <label class="fs-label">Jenis Listing</label>
                            {{ $fields->get('jenis_listing_id') }}
                        </div>
                        <div>
                            <label class="fs-label">Jenis Objek</label>
                            {{ $fields->get('jenis_objek_id') }}
                        </div>
                    </div>
                </div>

                <div class="fs-footer">
                    <button type="button" wire:click="resetFilters" class="fs-btn-reset">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        Reset Filter
                    </button>
                </div>

            </div>
        </aside>

        {{-- ═══════════════════════════════════════════
             TABLE PANEL
        ═══════════════════════════════════════════ --}}
        <section class="flex-1 min-w-0">
            <div class="ft-card">

                {{-- Custom table header matching filter sidebar style --}}
                <div class="fc-header">
                    <div class="fc-header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
                        </svg>
                    </div>
                    <div>
                        <h2>Bank Data Pembanding</h2>
                        <p>Daftar properti pembanding tersedia</p>
                    </div>
                    {{-- live record count --}}
                    <div class="fc-header-right">
                        <span class="ft-count-chip">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:11px;height:11px;margin-right:4px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/>
                            </svg>
                            {{ $this->table->getRecords()->total() }} data
                        </span>
                    </div>
                </div>

                {{-- Filament table (its own header/toolbar is hidden via CSS) --}}
                {{ $this->table }}

            </div>
        </section>

    </div>
</x-filament-panels::page>
