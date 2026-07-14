<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18px; size: A4 landscape; }
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #475569; margin-bottom: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 4px; vertical-align: top; }
        th { background: #0f172a; color: white; font-size: 7px; text-align: left; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { color: #64748b; margin-top: 8px; }
    </style>
</head>
<body>
    <h1>Ringkasan Data Pembanding</h1>
    <div class="meta">
        Dibuat {{ $metadata['Dibuat pada'] ?? now()->format('Y-m-d H:i:s') }} oleh {{ $metadata['Diminta oleh'] ?? '-' }}
        · {{ number_format($records->count(), 0, ',', '.') }} data
    </div>
    <table>
        <thead><tr>@foreach ($columns as $column)<th>{{ $registry->headings([$column])[0] }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach ($records as $record)
                <tr>@foreach ($columns as $column)<td>{{ $registry->displayValue($record, $column) }}</td>@endforeach</tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">Dokumen internal · Filter: {{ json_encode($metadata['Filter'] ?? [], JSON_UNESCAPED_UNICODE) }}</div>
</body>
</html>
