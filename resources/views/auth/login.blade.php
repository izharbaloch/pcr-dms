<x-guest-layout>
    <h2 class="auth-title">Welcome back</h2>
    <p class="auth-lead">Sign in to your account to continue.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <div class="relative">
                <x-icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                <x-text-input id="email" class="has-icon" type="email" name="email" :value="old('email')" placeholder="you@organization.org" required autofocus autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" class="!mb-0" />
                @if (Route::has('password.request'))
                    <a class="auth-link text-sm" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
            <div class="mt-1.5">
                <x-password-input id="password" name="password" placeholder="Enter your password" required autocomplete="current-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="inline-flex items-center gap-2">
            <input id="remember_me" type="checkbox" class="checkbox" name="remember">
            <span class="text-sm text-slate-600">{{ __('Remember me') }}</span>
        </label>

        <x-primary-button class="btn-block">
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <p class="mt-8 text-center text-sm text-slate-500">
            Don't have an account?
            <a href="{{ route('register') }}" class="auth-link">Create one</a>
        </p>
    @endif
</x-guest-layout>
