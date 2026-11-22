{{--
    Searchable dropdown for a Livewire listing. Alpine handles opening, closing and focus; Livewire loads
    the options. The component must provide showOptions(), choose(), clearFilter(), and the $optionsFor
    and $optionSearch properties. $options is [value => label] for the dropdown whose options were requested.
--}}
@props(['name', 'label', 'selected' => null, 'active' => false, 'options' => [], 'disabled' => false])

<div class="relative"
     x-data="{
         open: false,
         toggle() {
             this.open = ! this.open
             this.open ? $wire.showOptions('{{ $name }}') : this.close()
             this.$nextTick(() => this.$refs.search?.focus())
         },
         close() {
             if (! this.open) return
             this.open = false
             $wire.optionsFor = null
         },
     }"
     @click.outside="close()"
     @keydown.escape="close()">
    <button type="button" @click="toggle()" @disabled($disabled) aria-haspopup="listbox" :aria-expanded="open"
            class="select flex items-center justify-between gap-2 border border-slate-300 bg-white text-left disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400">
        <span @class(['truncate', 'text-slate-900' => $selected, 'text-slate-500' => ! $selected])>{{ $selected ?? $label }}</span>
        <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-slate-400" />
    </button>

    <div x-show="open" x-cloak class="menu-panel absolute z-30 mt-1.5 w-full min-w-56 p-2">
        <input type="search" x-ref="search" wire:model.live.debounce.250ms="optionSearch" class="input mb-2 !py-2" placeholder="Search…" aria-label="Search {{ $name }}">

        <ul class="max-h-60 overflow-y-auto" role="listbox">
            <li><button type="button" @click="close()" wire:click="clearFilter('{{ $name }}')" class="menu-item font-medium text-slate-500">{{ $label }}</button></li>
            @if ($active)
                @forelse ($options as $value => $text)
                    <li wire:key="{{ $name }}-option-{{ $value }}">
                        <button type="button" @click="open = false" wire:click="choose('{{ $name }}', @js($value))" role="option" class="menu-item">{{ $text }}</button>
                    </li>
                @empty
                    <li class="px-4 py-3 text-sm text-slate-500">No matches found.</li>
                @endforelse
            @endif
        </ul>
    </div>
</div>
