<div>
    @if ($paginator->total() > 0)
        <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col items-center justify-between gap-3 px-5 py-4 sm:flex-row sm:px-6">
            <p class="text-sm text-slate-500">
                Showing <span class="font-medium text-slate-700">{{ number_format($paginator->firstItem()) }}</span>
                to <span class="font-medium text-slate-700">{{ number_format($paginator->lastItem()) }}</span>
                of <span class="font-medium text-slate-700">{{ number_format($paginator->total()) }}</span> results
            </p>

            @if ($paginator->hasPages())
                <div class="flex items-center gap-1">
                    <button type="button" class="page-btn" wire:click="previousPage('{{ $paginator->getPageName() }}')" @disabled($paginator->onFirstPage()) aria-label="{{ __('pagination.previous') }}">
                        <x-icon name="chevron-right" class="h-4 w-4 rotate-180" />
                    </button>

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="px-1.5 text-slate-400">{{ $element }}</span>
                        @else
                            @foreach ($element as $page => $url)
                                <button type="button"
                                        wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}"
                                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                        @class(['page-btn', 'page-btn-active' => $page == $paginator->currentPage()])
                                        @if ($page == $paginator->currentPage()) aria-current="page" @endif
                                        aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</button>
                            @endforeach
                        @endif
                    @endforeach

                    <button type="button" class="page-btn" wire:click="nextPage('{{ $paginator->getPageName() }}')" @disabled(! $paginator->hasMorePages()) aria-label="{{ __('pagination.next') }}">
                        <x-icon name="chevron-right" class="h-4 w-4" />
                    </button>
                </div>
            @endif
        </nav>
    @endif
</div>
