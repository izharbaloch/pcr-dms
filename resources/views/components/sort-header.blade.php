{{-- Sortable table heading for Livewire listings: calls the component's sort() method. --}}
@props(['column', 'label', 'sortBy', 'sortDirection'])

@php($active = $sortBy === $column)

<th {{ $attributes }} aria-sort="{{ $active ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <button type="button" wire:click="sort('{{ $column }}')" class="table-sort">
        {{ $label }}
        @if ($active)
            <x-icon name="chevron-down" @class(['h-3.5 w-3.5 text-brand-600', 'rotate-180' => $sortDirection === 'asc']) />
        @else
            <x-icon name="sort" class="h-3.5 w-3.5 text-slate-300" />
        @endif
    </button>
</th>
