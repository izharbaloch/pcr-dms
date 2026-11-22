@php
    $limit = $this->sources->get($source)['capacity'] ?? 0;
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <x-donor-banner :donor="$this->donor" />

    <form wire:submit="save" class="space-y-6" novalidate>
        @error('form')
            <div class="alert-error"><x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0" /> {{ $message }}</div>
        @enderror

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Which plan or gift pays?</h2>
                    <p class="card-subtitle">Only this donor's Orphan Sponsorship and Orphan Care Program plans and gifts with money left are listed.</p>
                </div>
                <span wire:loading wire:target="source" class="text-xs text-slate-400">Updating…</span>
            </div>
            <div class="card-body">
                @if ($this->sources->isEmpty())
                    <x-empty-state icon="folder" title="Nothing to sponsor from">
                        This donor has no active plan or one-time gift with enough left to sponsor a child.
                        <a href="{{ route('donations.create', $this->donor) }}" class="mt-3 block font-medium text-ocean-700 hover:underline">Record a donation</a>
                    </x-empty-state>
                @else
                    <select wire:model.live="source" class="select" aria-label="Plan or gift">
                        <option value="">Select a plan or gift</option>
                        @foreach ($this->sources as $key => $item)
                            <option value="{{ $key }}">{{ $item['label'] }} ({{ $item['capacity'] }} {{ str('child')->plural($item['capacity']) }} left)</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('source')" />
                @endif
            </div>
        </section>

        @if ($limit > 0)
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Which children?</h2>
                        <p class="card-subtitle">Up to {{ $limit }} {{ str('child')->plural($limit) }}, chosen from the children available for sponsorship.</p>
                    </div>
                </div>
                <div class="card-body">
                    <x-child-picker :chosen="$this->chosenChildren" :results="$this->childResults" :limit="$limit" />
                    <x-input-error :messages="$errors->get('selected_children')" />
                </div>
            </section>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('donors.show', $this->donor) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Create sponsorship</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        @endif
    </form>
</div>
