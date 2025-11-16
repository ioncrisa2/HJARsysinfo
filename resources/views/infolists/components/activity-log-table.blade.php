@php
    $properties = $getRecord()->properties ?? collect();
    $old = $properties['old'] ?? [];
    $attributes = $properties['attributes'] ?? [];
    $keys = collect(array_keys($old))->merge(array_keys($attributes))->unique();
@endphp

@if($keys->isNotEmpty())
    <div class="fi-ta-ctn overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-start text-sm font-medium text-gray-950 dark:text-white">Field</th>
                    <th class="px-4 py-3 text-start text-sm font-medium text-danger-600 dark:text-danger-400">Sebelum</th>
                    <th class="px-4 py-3 text-start text-sm font-medium text-primary-600 dark:text-primary-400">Sesudah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @foreach($keys as $key)
                    @continue(in_array($key, ['created_at', 'updated_at', 'deleted_at']))
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="px-4 py-3 text-sm text-gray-950 dark:text-white">
                            {{ str($key)->replace(['_', '.'], ' ')->title() }}
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-danger-600 dark:text-danger-400 bg-danger-50/50 dark:bg-danger-500/10">
                            @if(isset($old[$key]))
                                @if(is_array($old[$key]))
                                    <pre class="text-xs">{{ json_encode($old[$key], JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    {{ \Illuminate\Support\Str::limit($old[$key], 100) }}
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-gray-600">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-primary-600 dark:text-primary-400 bg-primary-50/50 dark:bg-primary-500/10">
                             @if(isset($attributes[$key]))
                                @if(is_array($attributes[$key]))
                                    <pre class="text-xs">{{ json_encode($attributes[$key], JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    {{ \Illuminate\Support\Str::limit($attributes[$key], 100) }}
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-gray-600">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-sm text-gray-500 dark:text-gray-400">
        Tidak ada detail perubahan yang tercatat.
    </div>
@endif
