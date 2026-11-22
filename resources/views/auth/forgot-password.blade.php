<x-guest-layout>
    <h2 class="auth-title">Forgot password?</h2>
    <p class="auth-lead">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <div class="relative">
                <x-icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                <x-text-input id="email" class="has-icon" type="email" name="email" :value="old('email')" placeholder="you@organization.org" required autofocus />
            </div>
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button class="btn-block">
            {{ __('Email Password Reset Link') }}
        </x-primary-button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="auth-link">Back to log in</a>
    </p>
</x-guest-layout>
