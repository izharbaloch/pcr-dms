<x-guest-layout>
    <h2 class="auth-title">Create your account</h2>
    <p class="auth-lead">Fill in your details to get started.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-7 space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <div class="relative">
                <x-icon name="user" class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                <x-text-input id="name" class="has-icon" type="text" name="name" :value="old('name')" placeholder="Your full name" required autofocus autocomplete="name" />
            </div>
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <div class="relative">
                <x-icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                <x-text-input id="email" class="has-icon" type="email" name="email" :value="old('email')" placeholder="you@organization.org" required autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input id="password" name="password" placeholder="Create a password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-password-input id="password_confirmation" name="password_confirmation" placeholder="Repeat your password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="btn-block">
            {{ __('Register') }}
        </x-primary-button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" class="auth-link">Log in</a>
    </p>
</x-guest-layout>
