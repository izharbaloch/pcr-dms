<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('Profile') }}</h1>
        <p class="mt-1 text-sm text-slate-500">Manage your account details, password and security.</p>
    </x-slot>

    <div class="grid items-start gap-6 xl:grid-cols-3">
        <!-- Summary -->
        <div class="card overflow-hidden xl:sticky xl:top-24">
            <div class="h-20 bg-brand-700"></div>
            <div class="px-6 pb-6">
                <span class="-mt-10 flex h-20 w-20 items-center justify-center rounded-full border-4 border-white bg-sun-500 text-3xl font-bold text-brand-950">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <h2 class="mt-3 text-lg font-semibold text-slate-900">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <span class="badge-yellow">Email unverified</span>
                    @else
                        <span class="badge-green"><x-icon name="check-circle" class="h-3.5 w-3.5" /> Active account</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-2">
            <div class="card card-body">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card card-body">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card card-body border-red-200">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
