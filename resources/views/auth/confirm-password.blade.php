<x-guest-layout>
    <h2 class="auth-title">Confirm password</h2>
    <p class="auth-lead">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-7 space-y-5">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input id="password" name="password" placeholder="Enter your password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-primary-button class="btn-block">
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</x-guest-layout>
