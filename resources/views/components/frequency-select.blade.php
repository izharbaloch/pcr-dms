{{-- Frequency filter for a Livewire listing (binds to its $frequency). --}}
<select wire:model.live="frequency" class="select" aria-label="Frequency">
    <option value="">All frequencies</option>
    <option value="{{ \App\Models\Donation::ONE_TIME }}">{{ \App\Models\Donation::ONE_TIME }}</option>
    @foreach (array_keys(\App\Models\Donation::FREQUENCY_GROUPS) as $label)
        <option value="{{ $label }}">{{ $label }}</option>
    @endforeach
</select>
