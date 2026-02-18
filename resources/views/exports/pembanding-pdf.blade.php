<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 16px 18px;
            size: A4 landscape;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
        }
        .record {
            page-break-after: always;
            display: table;
            width: 100%;
        }
        .header {
            margin-bottom: 8px;
        }
        h1 {
            font-size: 14px;
            margin: 0 0 8px;
        }
        h2 {
            font-size: 11px;
            margin: 8px 0 4px;
            background: #f3f4f6;
            padding: 4px 6px;
            border-left: 3px solid #3b82f6;
        }

        /* Two Column Layout */
        .columns {
            display: table;
            width: 100%;
        }
        .column-left,
        .column-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .column-left {
            padding-right: 8px;
        }
        .column-right {
            padding-left: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        td {
            padding: 4px 6px;
            vertical-align: top;
            border: 1px solid #e5e7eb;
            font-size: 9px;
        }
        .label {
            width: 38%;
            font-weight: 600;
            background: #f8fafc;
        }
        .panel {
            border: 1px solid #e5e7eb;
            padding: 6px;
            margin-bottom: 8px;
        }
        .image {
            border: 1px solid #e5e7eb;
            padding: 6px;
            text-align: center;
            background: #fafafa;
        }
        .image img {
            max-width: 100%;
            max-height: 280px;
            object-fit: contain;
        }
        .small {
            font-size: 9px;
            color: #4b5563;
            font-weight: 600;
        }
        .notes-box {
            min-height: 100px;
            padding: 6px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            font-size: 9px;
        }
    </style>
</head>
<body>
@foreach ($records as $record)
    <div class="record">
        <div class="header">
            <h1>Data Pembanding #{{ $record->id }}</h1>
        </div>

        <div class="columns">
            <!-- LEFT COLUMN -->
            <div class="column-left">
                <h2>Informasi Utama</h2>
                <table>
                    <tr><td class="label">Alamat</td><td>{{ $record->alamat_data }}</td></tr>
                    <tr><td class="label">Koordinat</td><td>{{ $record->latitude }}, {{ $record->longitude }}</td></tr>
                    <tr><td class="label">Jenis Listing / Objek</td><td>{{ optional($record->jenisListing)->name }} / {{ optional($record->jenisObjek)->name }}</td></tr>
                    <tr><td class="label">Status Pemberi Info</td><td>{{ optional($record->statusPemberiInformasi)->name }}</td></tr>
                    <tr><td class="label">Tanggal Data</td><td>{{ optional($record->tanggal_data)?->format('Y-m-d') }}</td></tr>
                    <tr><td class="label">Harga</td><td>Rp {{ number_format($record->harga, 0, ',', '.') }}</td></tr>
                </table>

                <h2>Detail Fisik</h2>
                <table>
                    <tr><td class="label">Luas Tanah (m²)</td><td>{{ $record->luas_tanah }}</td></tr>
                    <tr><td class="label">Luas Bangunan (m²)</td><td>{{ $record->luas_bangunan }}</td></tr>
                    <tr><td class="label">Lebar Depan / Jalan</td><td>{{ $record->lebar_depan }} / {{ $record->lebar_jalan }}</td></tr>
                    <tr><td class="label">Rasio Tapak</td><td>{{ $record->rasio_tapak }}</td></tr>
                    <tr><td class="label">Bentuk / Posisi</td><td>{{ optional($record->bentukTanah)->name }} / {{ optional($record->posisiTanah)->name }}</td></tr>
                    <tr><td class="label">Kondisi Tanah</td><td>{{ optional($record->kondisiTanah)->name }}</td></tr>
                    <tr><td class="label">Dokumen Tanah</td><td>{{ optional($record->dokumenTanah)->name }}</td></tr>
                    <tr><td class="label">Topografi</td><td>{{ optional($record->topografiRef)->name }}</td></tr>
                    <tr><td class="label">Peruntukan</td><td>{{ optional($record->peruntukanRef)->name }}</td></tr>
                </table>

                <div class="panel">
                    <div class="small" style="margin-bottom:4px;">Pemberi Informasi</div>
                    <table>
                        <tr><td class="label">Nama</td><td>{{ $record->nama_pemberi_informasi }}</td></tr>
                        <tr><td class="label">Telepon</td><td>{{ $record->nomer_telepon_pemberi_informasi }}</td></tr>
                        <tr><td class="label">Status</td><td>{{ optional($record->statusPemberiInformasi)->name }}</td></tr>
                    </table>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="column-right">
                @if ($record->image)
                    <div class="panel">
                        <div class="small" style="margin-bottom:4px;">Foto Data Pembanding</div>
                        <div class="image">
                            <img src="{{ public_path('storage/' . $record->image) }}" alt="Foto Pembanding">
                        </div>
                    </div>
                @endif

                <div class="panel">
                    <div class="small" style="margin-bottom:4px;">Catatan</div>
                    <div class="notes-box">
                        {!! nl2br(e($record->catatan)) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
</body>
</html>
