<div class="space-y-3">
    @forelse ($activities as $activity)
        @php
            $oldValues = data_get($activity->properties, 'old', []);
            $newValues = data_get($activity->properties, 'attributes', []);
            $keys = array_unique(array_merge(array_keys((array) $oldValues), array_keys((array) $newValues)));
        @endphp

        <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
            <div class="mb-2 flex items-center justify-between gap-2">
                <span class="rounded bg-gray-100 px-2 py-0.5 text-[11px] font-semibold uppercase dark:bg-gray-800">
                    {{ $activity->event ? strtoupper($activity->event) : 'UPDATE' }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ optional($activity->created_at)->format('d M Y H:i') }}
                </span>
            </div>

            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ $activity->description ?? '-' }}
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Oleh: {{ $activity->causer?->name ?? 'System' }}
            </div>

            @if (count($keys))
                <div class="mt-3 space-y-1 border-t border-gray-100 pt-2 text-xs dark:border-white/10">
                    @foreach ($keys as $key)
                        @php
                            $before = data_get($oldValues, $key);
                            $after = data_get($newValues, $key);
                        @endphp

                        @continue($before === $after)

                        <div class="flex gap-1">
                            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $key }}:</span>
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ is_scalar($before) || $before === null ? ($before ?? '-') : json_encode($before) }}
                            </span>
                            <span class="text-gray-400">-></span>
                            <span class="text-gray-700 dark:text-gray-200">
                                {{ is_scalar($after) || $after === null ? ($after ?? '-') : json_encode($after) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
            Belum ada riwayat perubahan untuk data ini.
        </div>
    @endforelse
</div>
