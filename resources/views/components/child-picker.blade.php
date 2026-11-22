{{--
    Search-and-pick of children available for sponsorship. The component must use the PicksChildren trait.
    $limit is how many children may be picked.
--}}
@props(['chosen', 'results', 'limit'])

<div class="space-y-3">
    @if ($chosen->isNotEmpty())
        <ul class="flex flex-wrap gap-2">
            @foreach ($chosen as $child)
                <li wire:key="chosen-child-{{ $child->id }}" class="badge-green !px-3 !py-1.5 !text-sm">
                    {{ $child->picker_label }}
                    <button type="button" wire:click="removeChild({{ $child->id }})" class="-mr-1 rounded-full p-0.5 hover:bg-brand-100" aria-label="Remove {{ $child->name }}">
                        <x-icon name="x" class="h-3.5 w-3.5" />
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($chosen->count() < $limit)
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
            <input type="search" wire:model.live.debounce.300ms="childSearch" class="input has-icon" placeholder="Search children by name or PKCR id…" aria-label="Search children" autocomplete="off">
            <span wire:loading wire:target="childSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">Searching…</span>
        </div>

        <ul class="max-h-64 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200 bg-white">
            @forelse ($results as $child)
                <li wire:key="child-result-{{ $child->id }}">
                    <button type="button" wire:click="addChild({{ $child->id }})" class="block w-full px-3.5 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">{{ $child->picker_label }}</button>
                </li>
            @empty
                <li class="px-3.5 py-3 text-sm text-slate-500">No available children match.</li>
            @endforelse
        </ul>
    @endif

    <p class="text-xs text-slate-500">{{ $chosen->count() }} of {{ $limit }} {{ str('child')->plural($limit) }} selected.</p>
</div>
