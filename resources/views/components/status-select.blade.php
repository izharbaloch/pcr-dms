{{-- Active / cancelled / ended filter for the sponsorship and subscription listings (binds to $status). --}}
<select wire:model.live="status" class="select" aria-label="Status">
    <option value="active">Active</option>
    <option value="cancelled">Cancelled</option>
    <option value="ended">Ended</option>
    <option value="all">All statuses</option>
</select>
