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
    <body class="font-sans text-slate-900">
        <div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] 2xl:grid-cols-2">

            <!-- Brand panel (desktop) -->
            <aside class="relative hidden overflow-hidden bg-brand-900 lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16">
                <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-brand-700/40"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-ocean-700/30"></div>
                <div class="pointer-events-none absolute bottom-24 right-16 h-3 w-24 rounded-full bg-sun-500"></div>

                <a href="{{ url('/') }}" class="relative flex items-center gap-3">
                    <x-application-logo class="h-11 w-11" />
                    <span class="text-lg font-bold text-white">{{ config('app.name', 'PCR DMS') }}</span>
                </a>

                <div class="relative max-w-md">
                    <h1 class="text-3xl font-bold leading-tight tracking-tight text-white xl:text-4xl">Every record, <span class="text-sun-400">organised</span> and under control.</h1>
                    <p class="mt-4 text-base leading-relaxed text-brand-100/80">One secure workspace for your teams to store, share and approve data across every department.</p>

                    <ul class="mt-8 space-y-4 text-sm text-brand-50">
                        <li class="flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10"><x-icon name="shield-check" class="h-[18px] w-[18px] text-sun-400" /></span> Secure, role-based access</li>
                        <li class="flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10"><x-icon name="folder" class="h-[18px] w-[18px] text-sun-400" /></span> Departments and records in one place</li>
                        <li class="flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10"><x-icon name="check-circle" class="h-[18px] w-[18px] text-sun-400" /></span> Faster approvals, full activity trail</li>
                    </ul>
                </div>

                <p class="relative text-xs text-brand-200/60">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </aside>

            <!-- Form panel -->
            <main class="flex flex-col items-center justify-center bg-white px-6 py-10 sm:px-10">
                <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                    <x-application-logo class="h-10 w-10" />
                    <span class="text-lg font-bold text-slate-900">{{ config('app.name', 'PCR DMS') }}</span>
                </a>

                <div class="auth-card">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
