<x-guest-layout>
    <span class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-ocean-50 text-ocean-700">
        <x-icon name="mail" class="h-6 w-6" />
    </span>
    <h2 class="auth-title">Verify your email</h2>
    <p class="auth-lead">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert-success mt-5">
            <x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0" />
            <span>{{ __('A new verification link has been sent to the email address you provided during registration.') }}</span>
        </div>
    @endif

    <div class="mt-7 flex flex-wrap items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button>
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="auth-link text-sm">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
