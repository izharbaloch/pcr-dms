{{-- The donor a form is for, shown at the top of forms opened from the donor profile. --}}
@props(['donor'])

<div class="flex items-center gap-3 rounded-xl border border-brand-200 bg-brand-50/60 p-4">
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">{{ $donor->initials }}</span>
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-slate-900">{{ $donor->name }} <span class="font-normal text-slate-400">{{ $donor->donor_no }}</span></p>
        <p class="truncate text-xs text-slate-500">{{ $donor->email }} @if ($donor->contact_no) · {{ $donor->contact_no }} @endif</p>
    </div>
    <a href="{{ route('donors.show', $donor) }}" class="btn-secondary !px-3 !py-1.5">Back to profile</a>
</div>
