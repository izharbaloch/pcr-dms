<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'PCR DMS') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        <div class="relative flex min-h-screen flex-col overflow-hidden bg-brand-900">
            <div class="pointer-events-none absolute -right-32 -top-32 h-[28rem] w-[28rem] rounded-full bg-brand-700/40"></div>
            <div class="pointer-events-none absolute -bottom-40 -left-24 h-[30rem] w-[30rem] rounded-full bg-ocean-700/30"></div>

            <header class="relative mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-10" />
                    <span class="text-lg font-bold text-white">{{ config('app.name', 'PCR DMS') }}</span>
                </a>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-2">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn bg-sun-500 text-brand-950 hover:bg-sun-400">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn text-white hover:bg-white/10">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn bg-sun-500 text-brand-950 hover:bg-sun-400">Register</a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <main class="relative mx-auto flex w-full max-w-7xl flex-1 items-center px-6 py-12 lg:px-8">
                <div class="max-w-2xl">
                    <span class="badge bg-white/10 text-sun-300 ring-1 ring-inset ring-white/15">Data Management System</span>
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl">Every record, <span class="text-sun-400">organised</span> and under control.</h1>
                    <p class="mt-5 text-lg leading-relaxed text-brand-100/80">One secure workspace for your teams to store, share and approve data across every department.</p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn bg-sun-500 px-6 py-3 text-brand-950 hover:bg-sun-400">Go to dashboard</a>
                        @else
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="btn bg-sun-500 px-6 py-3 text-brand-950 hover:bg-sun-400">Log in</a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn border border-white/25 px-6 py-3 text-white hover:bg-white/10">Create account</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </main>

            <footer class="relative px-6 py-5 text-center text-xs text-brand-200/60">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</footer>
        </div>
    </body>
</html>
