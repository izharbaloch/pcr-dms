{{-- Active filters of a Livewire listing as removable chips. $filters is [filter => display value]; needs clearFilter() and resetFilters(). --}}
@props(['filters'])

@if ($filters)
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm text-slate-500">Filters:</span>
        @foreach ($filters as $filter => $value)
            <span class="badge-green !py-1 pl-3 pr-1.5" wire:key="chip-{{ $filter }}">
                {{ ucfirst($filter) }}: <span class="max-w-[14rem] truncate font-semibold">{{ $value }}</span>
                <button type="button" wire:click="clearFilter('{{ $filter }}')" class="rounded-full p-0.5 hover:bg-brand-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Remove {{ $filter }} filter">
                    <x-icon name="x" class="h-3.5 w-3.5" />
                </button>
            </span>
        @endforeach
        <button type="button" wire:click="resetFilters" class="ml-1 text-sm font-medium text-ocean-700 hover:underline">Clear all filters</button>
    </div>
@endif
