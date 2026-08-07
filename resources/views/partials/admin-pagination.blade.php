@php
    // $countExpr : Alpine expression rendering the "N items" label
    // $wrapper   : override outer padding/border when the host card differs
    // $showCount : show range + item count (default true)
    $countExpr = $countExpr ?? "filteredItems().length + ' item'";
    $wrapper = $wrapper ?? 'px-5 sm:px-6 py-3.5 border-t border-gray-100 bg-gray-50/70';
    $showCount = ($showCount ?? true) !== false;
@endphp
<div class="{{ $wrapper }} flex flex-col gap-3 sm:flex-row sm:items-center {{ $showCount ? 'sm:justify-between' : 'sm:justify-end' }}">
    @if ($showCount)
    <p class="text-sm text-gray-500">
        <span class="font-semibold text-gray-800" x-text="rangeStart() + '–' + rangeEnd()"></span>
        <span class="text-gray-400">/</span>
        <span x-text="{{ $countExpr }}"></span>
    </p>
    @endif

    <nav class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white p-1 shadow-sm" aria-label="Pagination">
        <button
            type="button"
            class="inline-flex h-8 items-center gap-1 rounded-lg px-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent disabled:hover:text-gray-600"
            :disabled="page <= 1"
            @click="goToPage(page - 1)"
            :aria-label="common().prev"
        >
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            <span class="hidden sm:inline" x-text="common().prev"></span>
        </button>

        <span class="mx-0.5 h-5 w-px bg-gray-200" aria-hidden="true"></span>

        <template x-for="(entry, index) in pageNumbers()" :key="index">
            <span class="inline-flex">
                <span x-show="entry === '…'" class="inline-flex h-8 w-7 items-center justify-center text-sm text-gray-400 select-none">…</span>
                <button
                    x-show="entry !== '…'"
                    type="button"
                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm font-semibold transition-colors"
                    :class="entry === page
                        ? 'bg-gray-900 text-white shadow-sm'
                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                    :aria-current="entry === page ? 'page' : null"
                    @click="goToPage(entry)"
                    x-text="entry"
                ></button>
            </span>
        </template>

        <span class="mx-0.5 h-5 w-px bg-gray-200" aria-hidden="true"></span>

        <button
            type="button"
            class="inline-flex h-8 items-center gap-1 rounded-lg px-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent disabled:hover:text-gray-600"
            :disabled="page >= pageCount()"
            @click="goToPage(page + 1)"
            :aria-label="common().next"
        >
            <span class="hidden sm:inline" x-text="common().next"></span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </button>
    </nav>
</div>
