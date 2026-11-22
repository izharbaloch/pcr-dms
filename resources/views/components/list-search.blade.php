{{-- Live search box for a Livewire listing (binds to its $search). --}}
@props(['placeholder'])

<div class="relative">
    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
    <input type="search" wire:model.live.debounce.300ms="search" class="input has-icon pr-10" placeholder="{{ $placeholder }}" aria-label="Search">
    <span wire:loading wire:target="search" class="hidden absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin rounded-full border-2 border-slate-200 border-t-brand-600"></span>
</div>
