{{-- Donor name (linked to the profile) with their email underneath. $donor may be null for orphaned records. --}}
@props(['donor'])

@if ($donor)
    <a href="{{ route('donors.show', $donor) }}" class="block max-w-[13rem] truncate font-medium text-slate-900 hover:text-brand-700">{{ $donor->name }}</a>
    <span class="block max-w-[13rem] truncate text-xs text-slate-400">{{ $donor->email }}</span>
@else
    <span class="text-slate-400">Unknown donor</span>
@endif
