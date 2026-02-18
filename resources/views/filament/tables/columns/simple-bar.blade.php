@php
    $state = $getState() ?? [];
    $pct = (float) ($state['pct'] ?? 0);
    $color = (string) ($state['color'] ?? '#64748b');

    $width = (int) round($pct * 100);
    $width = max(2, min(100, $width));
@endphp

<div class="w-full">
    <div class="h-2 w-full rounded bg-gray-200/70 dark:bg-gray-700/60 overflow-hidden">
        <div class="h-2 rounded" style="width: {{ $width }}%; background: {{ $color }};"></div>
    </div>
</div>
