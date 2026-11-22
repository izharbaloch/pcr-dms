{{-- Sidebar. State (mobileOpen, collapsed) is provided by layouts/app.blade.php --}}

<!-- Mobile backdrop -->
<div x-show="mobileOpen" x-cloak @click="mobileOpen = false"
     x-transition.opacity
     class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"></div>

<aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-brand-900 transition-all duration-200 max-lg:-translate-x-full lg:translate-x-0"
       :class="{ 'max-lg:!translate-x-0': mobileOpen, 'lg:w-[4.5rem]': collapsed }"
       aria-label="Sidebar">

    <!-- Brand -->
    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-white/10 px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-sun-400 rounded-lg">
            <x-application-logo class="h-9 w-9 shrink-0" />
            <span class="min-w-0 leading-tight" :class="collapsed && 'lg:hidden'">
                <span class="block truncate text-sm font-bold text-white">{{ config('app.name', 'PCR DMS') }}</span>
                <span class="block truncate text-[11px] text-brand-200/70">Data Management</span>
            </span>
        </a>
        <button type="button" class="icon-btn !h-8 !w-8 text-brand-200 hover:!bg-white/10 hover:!text-white lg:hidden" @click="mobileOpen = false" aria-label="Close menu">
            <x-icon name="x" class="h-5 w-5" />
        </button>
    </div>

    <!-- Links -->
    <nav class="flex-1 overflow-y-auto px-3 pb-4">
        <p class="side-label" :class="collapsed && 'lg:invisible'">Main</p>
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" title="Dashboard">
            <x-icon name="home" class="h-5 w-5 shrink-0" />
            <span class="truncate" :class="collapsed && 'lg:hidden'">{{ __('Dashboard') }}</span>
        </x-nav-link>

        <p class="side-label" :class="collapsed && 'lg:invisible'">Account</p>
        <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" title="Profile">
            <x-icon name="user" class="h-5 w-5 shrink-0" />
            <span class="truncate" :class="collapsed && 'lg:hidden'">{{ __('Profile') }}</span>
        </x-nav-link>
    </nav>

    <!-- Footer: user + collapse -->
    <div class="shrink-0 border-t border-white/10 p-3">
        <div class="flex items-center gap-3 rounded-lg px-2 py-2">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sun-500 text-sm font-bold text-brand-950">{{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
            <span class="min-w-0 leading-tight" :class="collapsed && 'lg:hidden'">
                <span class="block truncate text-sm font-medium text-white">{{ Auth::user()->name }}</span>
                <span class="block truncate text-xs text-brand-200/70">{{ Auth::user()->email }}</span>
            </span>
        </div>
        <button type="button" @click="toggleCollapse()" class="side-link mt-1 hidden w-full lg:flex" :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <x-icon name="chevrons-left" class="h-5 w-5 shrink-0 transition" ::class="collapsed && 'rotate-180'" />
            <span :class="collapsed && 'lg:hidden'">Collapse</span>
        </button>
    </div>
</aside>
