{{-- Password field with a show/hide toggle. Pass id, name, autocomplete etc. as attributes. --}}
<div class="relative" x-data="{ show: false }">
    <x-icon name="lock" class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
    <input :type="show ? 'text' : 'password'" {{ $attributes->merge(['class' => 'input has-icon pr-11']) }}>
    <button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-slate-400 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :aria-label="show ? 'Hide password' : 'Show password'">
        <x-icon name="eye" class="h-[18px] w-[18px]" x-show="!show" />
        <x-icon name="eye-off" class="h-[18px] w-[18px]" x-show="show" x-cloak />
    </button>
</div>
