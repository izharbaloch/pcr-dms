@props(['icon', 'title'])

<div class="empty-state">
    <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><x-icon :name="$icon" class="h-6 w-6" /></span>
    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
    <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $slot }}</p>
</div>
