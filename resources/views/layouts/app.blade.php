<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'PCR DMS') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        @php
            // Breadcrumb label = the <h1> text of the page header.
            $pageTitle = '';
            if (isset($header) && preg_match('/<h1[^>]*>(.*?)<\/h1>/s', (string) $header, $titleMatch)) {
                $pageTitle = trim(strip_tags($titleMatch[1]));
            }
        @endphp

        <div x-data="{
                mobileOpen: false,
                collapsed: (() => { try { return localStorage.getItem('sidebar-collapsed') === '1'; } catch (e) { return false; } })(),
                toggleCollapse() {
                    this.collapsed = ! this.collapsed;
                    try { localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0'); } catch (e) {}
                },
            }"
            @keydown.escape.window="mobileOpen = false"
            class="min-h-screen">

            @include('layouts.navigation')

            <!-- Main column -->
            <div class="flex min-h-screen flex-col transition-[padding] duration-200" :class="collapsed ? 'lg:pl-[4.5rem]' : 'lg:pl-64'">

                <!-- Top bar -->
                <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button type="button" class="icon-btn lg:hidden" @click="mobileOpen = true" aria-label="Open menu">
                        <x-icon name="menu" />
                    </button>
                    <button type="button" class="icon-btn hidden lg:inline-flex" @click="toggleCollapse()" aria-label="Collapse or expand sidebar">
                        <x-icon name="menu" />
                    </button>

                    <!-- Search (UI only) -->
                    <form class="relative ml-1 hidden w-full max-w-sm md:block" role="search" onsubmit="return false">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                        <input type="search" placeholder="Search data, people…" class="input !rounded-lg !border-transparent !bg-slate-100 !py-2 pl-10 !shadow-none focus:!border-brand-500 focus:!bg-white">
                    </form>

                    <div class="ml-auto flex items-center gap-1 sm:gap-2">
                        <!-- Notifications (static demo data) -->
                        <x-dropdown align="right" width="80">
                            <x-slot name="trigger">
                                <button type="button" class="icon-btn" aria-label="Notifications">
                                    <x-icon name="bell" />
                                    <span class="absolute right-2.5 top-2.5 h-2 w-2 rounded-full bg-sun-500 ring-2 ring-white"></span>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="flex items-center justify-between px-4 pb-2 pt-1.5">
                                    <span class="text-sm font-semibold text-slate-900">Notifications</span>
                                    <span class="badge-yellow">3 new</span>
                                </div>
                                <div class="divide-y divide-slate-100 border-t border-slate-100">
                                    @foreach ([
                                        ['icon' => 'clock', 'tone' => 'bg-sun-100 text-sun-700', 'text' => 'Leave policy 2026 is waiting for your approval', 'time' => '10 min ago'],
                                        ['icon' => 'document', 'tone' => 'bg-ocean-100 text-ocean-700', 'text' => 'Ayesha Khan shared “Annual Budget.xlsx”', 'time' => '1 hour ago'],
                                        ['icon' => 'check-circle', 'tone' => 'bg-brand-100 text-brand-700', 'text' => 'Your dataset “Vendor Contract” was approved', 'time' => 'Yesterday'],
                                    ] as $n)
                                        <a href="#" class="flex gap-3 px-4 py-3 transition hover:bg-slate-50">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $n['tone'] }}"><x-icon name="{{ $n['icon'] }}" class="h-[18px] w-[18px]" /></span>
                                            <span class="min-w-0">
                                                <span class="block text-sm text-slate-700">{{ $n['text'] }}</span>
                                                <span class="text-xs text-slate-400">{{ $n['time'] }}</span>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                                <a href="#" class="block border-t border-slate-100 px-4 pb-1.5 pt-3 text-center text-sm font-medium text-ocean-700 hover:text-ocean-800">View all notifications</a>
                            </x-slot>
                        </x-dropdown>

                        <!-- Profile -->
                        <x-dropdown align="right" width="56">
                            <x-slot name="trigger">
                                <button type="button" class="flex items-center gap-2.5 rounded-lg py-1 pl-1 pr-2 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">{{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                                    <span class="hidden max-w-[10rem] truncate text-sm font-medium text-slate-700 sm:block">{{ Auth::user()->name }}</span>
                                    <x-icon name="chevron-down" class="hidden h-4 w-4 text-slate-400 sm:block" />
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="border-b border-slate-100 px-4 pb-2.5 pt-1.5">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</p>
                                </div>
                                <x-dropdown-link :href="route('profile.edit')">
                                    <x-icon name="user" class="h-[18px] w-[18px] text-slate-400" /> {{ __('Profile') }}
                                </x-dropdown-link>

                                <!-- Authentication -->
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        <x-icon name="logout" class="h-[18px] w-[18px] text-slate-400" /> {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                <!-- Page content -->
                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    <div class="mx-auto w-full max-w-[90rem]">
                        @isset($header)
                            <div class="mb-6">
                                <!-- Breadcrumbs -->
                                <nav class="mb-2 flex items-center gap-1.5 text-sm text-slate-500" aria-label="Breadcrumb">
                                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 transition hover:text-brand-700">
                                        <x-icon name="home" class="h-4 w-4" /> Home
                                    </a>
                                    @if ($pageTitle !== '' && ! request()->routeIs('dashboard'))
                                        <x-icon name="chevron-right" class="h-3.5 w-3.5 text-slate-400" />
                                        <span class="font-medium text-slate-700">{{ $pageTitle }}</span>
                                    @endif
                                </nav>
                                {{ $header }}
                            </div>
                        @endisset

                        {{ $slot }}
                    </div>
                </main>

                <footer class="border-t border-slate-200 px-4 py-4 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </footer>
            </div>
        </div>
    </body>
</html>
