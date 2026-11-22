{{-- Pagination links and the rows-per-page select of a Livewire listing (binds to its $perPage). --}}
@props(['paginator', 'sizes'])

<div class="flex flex-col border-t border-slate-100 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex-1">{{ $paginator->links() }}</div>
    <label class="flex items-center gap-2 px-5 pb-4 text-sm text-slate-500 sm:px-6 sm:pb-0">
        Rows per page
        <select wire:model.live="perPage" class="select !w-20 !py-1.5">
            @foreach ($sizes as $size)
                <option value="{{ $size }}">{{ $size }}</option>
            @endforeach
        </select>
    </label>
</div>
