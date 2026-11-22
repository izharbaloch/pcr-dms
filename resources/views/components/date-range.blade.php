{{-- From / To date filters for a Livewire listing (bind to its $from and $to). --}}
@props(['from', 'to'])

<div class="grid grid-cols-2 gap-3 sm:col-span-2 lg:col-span-3 xl:col-span-2">
    <label class="flex items-center gap-2">
        <span class="shrink-0 text-sm text-slate-500">From</span>
        <input type="date" wire:model.live="from" max="{{ $to }}" class="input min-w-0">
    </label>
    <label class="flex items-center gap-2">
        <span class="shrink-0 text-sm text-slate-500">To</span>
        <input type="date" wire:model.live="to" min="{{ $from }}" class="input min-w-0">
    </label>
</div>
