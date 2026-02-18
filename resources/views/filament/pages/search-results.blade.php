<x-filament-panels::page>
    @php
        $paginator = $this->paginatedResults;
        $results = $paginator->items();
    @endphp

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="flex-1">
                    <label for="global-data-search" class="sr-only">Cari data</label>
                    <input
                        id="global-data-search"
                        type="search"
                        wire:model.live.debounce.400ms="query"
                        placeholder="Cari semua data aplikasi..."
                        class="block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                    />
                </div>

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
                >
                    Reset Filter
                </button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-12">
            <aside class="space-y-4 lg:col-span-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Filter Hasil</h3>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Menu Group</label>
                            <select
                                wire:model.live="menuGroup"
                                class="block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            >
                                <option value="">Semua Group</option>
                                @foreach ($this->menuGroupOptions as $group)
                                    <option value="{{ $group }}">{{ $group }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Menu</label>
                            <select
                                wire:model.live="menuName"
                                class="block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            >
                                <option value="">Semua Menu</option>
                                @foreach ($this->menuNameOptions as $menu)
                                    <option value="{{ $menu }}">{{ $menu }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Jenis Data</label>
                            <select
                                wire:model.live="resourceName"
                                class="block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            >
                                <option value="">Semua Jenis Data</option>
                                @foreach ($this->resourceNameOptions as $resource)
                                    <option value="{{ $resource }}">{{ $resource }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Data per halaman</label>
                            <select
                                wire:model.live="perPage"
                                class="block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                            >
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="lg:col-span-9">
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            Hasil Pencarian
                        </h2>
                        @if ($this->query !== '')
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                untuk "{{ $this->query }}"
                            </span>
                            <span class="rounded-md bg-primary-50 px-2 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/20 dark:text-primary-200">
                                {{ number_format($paginator->total()) }} hasil
                            </span>
                        @endif
                    </div>

                    @if ($this->query === '')
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            Ketik kata kunci untuk mencari data lintas semua menu.
                        </div>
                    @elseif (empty($results))
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            Tidak ada data yang cocok dengan pencarian dan filter saat ini.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($results as $index => $result)
                                <article
                                    wire:key="global-search-result-{{ md5($result['url'] . '|' . $index) }}"
                                    class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                >
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        <span class="rounded bg-gray-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $result['menu_group'] }}
                                        </span>
                                        <span class="rounded bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-700 dark:bg-blue-500/20 dark:text-blue-200">
                                            {{ $result['menu_name'] }}
                                        </span>
                                        <span class="rounded bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">
                                            {{ $result['resource_name'] }}
                                        </span>
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <h3 class="truncate text-base font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $result['title'] }}
                                            </h3>

                                            @if (!empty($result['details']))
                                                <dl class="mt-2 grid gap-x-4 gap-y-1 text-xs text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                                                    @foreach ($result['details'] as $label => $value)
                                                        <div class="flex gap-2">
                                                            <dt class="font-medium">{{ $label }}:</dt>
                                                            <dd class="truncate">{{ $value }}</dd>
                                                        </div>
                                                    @endforeach
                                                </dl>
                                            @endif
                                        </div>

                                        <a
                                            href="{{ $result['url'] }}"
                                            class="inline-flex items-center rounded-lg border border-primary-300 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-50 dark:border-primary-500/40 dark:text-primary-200 dark:hover:bg-primary-500/10"
                                        >
                                            Lihat Data
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $paginator->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
