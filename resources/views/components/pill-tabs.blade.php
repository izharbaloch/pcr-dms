{{-- Status sub-tabs inside a profile tab. $items = [key => ['label' => ..., 'count' => ...]]; clicking calls setStatus(key). --}}
@props(['items', 'active'])

<div class="flex flex-wrap gap-2 border-b border-slate-100 px-5 py-4 sm:px-6" role="tablist" aria-label="Status">
    @foreach ($items as $key => $item)
        <button type="button" role="tab" wire:click="setStatus('{{ $key }}')" aria-selected="{{ $active === $key ? 'true' : 'false' }}"
                @class(['btn !px-4 !py-2 border', 'border-brand-600 bg-brand-600 text-white' => $active === $key, 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => $active !== $key])>
            {{ $item['label'] }}
            <span @class(['badge', 'bg-white/20 text-white' => $active === $key, 'bg-slate-100 text-slate-500' => $active !== $key])>{{ number_format($item['count']) }}</span>
        </button>
    @endforeach
</div>
