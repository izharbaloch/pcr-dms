@php
    $donor = $this->donor;
    $tabs = [
        'overview' => ['label' => 'Overview', 'count' => null],
        'donations' => ['label' => 'Donations', 'count' => (int) $donor->donations_count],
        'sponsorships' => ['label' => 'Sponsorships', 'count' => $donor->sponsorships_count],
        'subscriptions' => ['label' => 'Subscriptions', 'count' => $donor->subscriptions_count],
    ];
    $statusTabs = [
        'sponsorships' => [
            'active' => ['label' => 'Active', 'count' => $donor->active_sponsorships_count],
            'cancelled' => ['label' => 'Cancelled', 'count' => $donor->cancelled_sponsorships_count],
            'ended' => ['label' => 'Ended', 'count' => $donor->ended_sponsorships_count],
        ],
        'subscriptions' => [
            'active' => ['label' => 'Active', 'count' => $donor->active_subscriptions_count],
            'cancelled' => ['label' => 'Cancelled', 'count' => $donor->cancelled_subscriptions_count],
            'ended' => ['label' => 'Ended', 'count' => $donor->ended_subscriptions_count],
        ],
    ];
    $cards = [
        ['label' => 'Total Donations', 'value' => number_format((int) $donor->donations_count), 'icon' => 'heart', 'tone' => 'bg-brand-50 text-brand-700'],
        ['label' => 'Total Donated', 'value' => '$'.number_format($donor->total_given, 2), 'icon' => 'currency', 'tone' => 'bg-sun-100 text-sun-700'],
        ['label' => 'Total Sponsorships', 'value' => number_format($donor->sponsorships_count), 'icon' => 'users', 'tone' => 'bg-ocean-50 text-ocean-700'],
        ['label' => 'Active Sponsorships', 'value' => number_format($donor->active_sponsorships_count), 'icon' => 'check-circle', 'tone' => 'bg-brand-50 text-brand-700'],
        ['label' => 'Cancelled Sponsorships', 'value' => number_format($donor->cancelled_sponsorships_count), 'icon' => 'ban', 'tone' => 'bg-red-50 text-red-600'],
    ];
@endphp

<div class="space-y-6">
    <!-- Profile header -->
    <section class="card overflow-hidden">
        <div class="h-20 bg-brand-800 sm:h-24">
            <div class="h-full w-full bg-[radial-gradient(circle_at_85%_20%,theme(colors.brand.600),transparent_55%)] opacity-70"></div>
        </div>
        <div class="px-5 pb-6 sm:px-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-end">
                <span class="-mt-10 flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl border-4 border-white bg-sun-500 text-3xl font-bold text-brand-950 shadow-card">{{ $donor->initials }}</span>
                <div class="min-w-0 flex-1 sm:pb-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="truncate text-2xl font-bold tracking-tight text-slate-900">{{ $donor->name }}</h2>
                        @if ($donor->is_organization)
                            <span class="badge-blue">Organization</span>
                        @else
                            <span class="badge-gray">Individual</span>
                        @endif
                    </div>
                    @if ($donor->is_organization && trim("{$donor->first_name} {$donor->last_name}") !== '')
                        <p class="mt-0.5 text-sm text-slate-500">{{ $donor->organization }}</p>
                    @endif
                </div>
                <a href="{{ route('donors.index') }}" class="btn-secondary self-start sm:self-auto">
                    <x-icon name="arrow-left" class="h-4 w-4" /> All donors
                </a>
            </div>

            <dl class="mt-6 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"><x-icon name="mail" class="h-[18px] w-[18px]" /></span>
                    <div class="min-w-0"><dt class="text-xs text-slate-400">Email</dt><dd class="truncate font-medium text-slate-800">{{ $donor->email }}</dd></div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"><x-icon name="phone" class="h-[18px] w-[18px]" /></span>
                    <div class="min-w-0"><dt class="text-xs text-slate-400">Phone</dt><dd class="truncate font-medium text-slate-800">{{ $donor->contact_no ?: '—' }}</dd></div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"><x-icon name="map-pin" class="h-[18px] w-[18px]" /></span>
                    <div class="min-w-0"><dt class="text-xs text-slate-400">Location</dt><dd class="truncate font-medium text-slate-800">{{ $donor->location ?: '—' }}</dd></div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"><x-icon name="calendar" class="h-[18px] w-[18px]" /></span>
                    <div class="min-w-0"><dt class="text-xs text-slate-400">Donor since</dt><dd class="truncate font-medium text-slate-800">{{ $donor->created_at?->format('d M Y') ?? '—' }}</dd></div>
                </div>
            </dl>
        </div>
    </section>

    <!-- Overview cards -->
    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6 2xl:grid-cols-5">
        @foreach ($cards as $card)
            <div @class(['card flex items-center gap-4 p-5 2xl:col-span-1', 'lg:col-span-2' => $loop->index < 3, 'lg:col-span-3' => $loop->index >= 3, 'sm:max-lg:last:col-span-2' => $loop->last])>
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $card['tone'] }}"><x-icon name="{{ $card['icon'] }}" class="h-6 w-6" /></span>
                <div class="min-w-0">
                    <p class="truncate text-sm text-slate-500">{{ $card['label'] }}</p>
                    <p class="truncate text-2xl font-bold tracking-tight text-slate-900">{{ $card['value'] }}</p>
                </div>
            </div>
        @endforeach
    </section>

    <!-- Tabs -->
    <section class="card">
        <div class="overflow-x-auto border-b border-slate-200 px-2 sm:px-4">
            <div class="flex min-w-max" role="tablist">
                @foreach ($tabs as $key => $item)
                    <button type="button" role="tab" wire:click="setTab('{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" @class(['tab', 'tab-active' => $tab === $key])>
                        {{ $item['label'] }}
                        @if ($item['count'] !== null)
                            <span @class(['badge', 'bg-brand-100 text-brand-700' => $tab === $key, 'bg-slate-100 text-slate-500' => $tab !== $key])>{{ number_format($item['count']) }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <div wire:loading.class="opacity-60" wire:target="setTab, setStatus" class="transition-opacity" role="tabpanel">
            @if ($tab === 'donations')
                <livewire:donors.donations-tab :donor-id="$donor->id" wire:key="tab-donations" />
            @elseif ($tab === 'sponsorships')
                <x-pill-tabs :items="$statusTabs['sponsorships']" :active="$status" />
                <livewire:donors.sponsorships-tab :donor-id="$donor->id" :scope="$status" wire:key="sponsorships-{{ $status }}" />
            @elseif ($tab === 'subscriptions')
                <x-pill-tabs :items="$statusTabs['subscriptions']" :active="$status" />
                <livewire:donors.subscriptions-tab :donor-key="$donor->auto" :scope="$status" wire:key="subscriptions-{{ $status }}" />
            @else
                @include('livewire.donors.overview')
            @endif
        </div>
    </section>
</div>
