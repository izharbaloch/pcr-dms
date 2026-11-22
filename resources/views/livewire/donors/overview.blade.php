{{-- Overview tab. Rendered inside livewire/donors/show.blade.php ($donor is the profile donor). --}}
<div class="grid gap-6 p-5 sm:p-6 xl:grid-cols-3">
    <!-- Donor information -->
    <div>
        <h3 class="card-title mb-4">Donor information</h3>
        <dl class="divide-y divide-slate-100 text-sm">
            @foreach ([
                'Donor number' => $donor->donor_no,
                'Name' => trim("{$donor->first_name} {$donor->last_name}"),
                'Organization' => $donor->organization,
                'Email' => $donor->email,
                'Phone' => $donor->contact_no,
                'Address' => $donor->full_address,
                'Newsletter' => $donor->newsletter_subscribed === null ? null : ($donor->newsletter_subscribed ? 'Subscribed' : 'Not subscribed'),
            ] as $label => $value)
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="shrink-0 text-slate-500">{{ $label }}</dt>
                    <dd class="min-w-0 break-words text-right font-medium text-slate-800">{{ $value ?: '—' }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <!-- Summaries -->
    <div class="space-y-6">
        <div>
            <h3 class="card-title mb-4">Donation summary</h3>
            <dl class="divide-y divide-slate-100 text-sm">
                @foreach ([
                    'Completed donations' => number_format((int) $donor->donations_count),
                    'Total donated' => '$'.number_format($donor->total_given, 2),
                    'Average donation' => '$'.number_format($donor->average_donation, 2),
                    'First donation' => $donor->first_donation_at?->format('d M Y'),
                    'Last donation' => $donor->last_donation_at?->format('d M Y'),
                ] as $label => $value)
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="font-medium text-slate-800">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-2 text-xs text-slate-400">Failed, pending and refunded payments are not counted.</p>
        </div>

        <div>
            <h3 class="card-title mb-4">Sponsorship summary</h3>
            <dl class="divide-y divide-slate-100 text-sm">
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">Total sponsorships</dt><dd class="font-medium text-slate-800">{{ number_format($donor->sponsorships_count) }}</dd></div>
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">Active</dt><dd><span class="badge-green">{{ number_format($donor->active_sponsorships_count) }}</span></dd></div>
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">Cancelled</dt><dd><span class="badge-red">{{ number_format($donor->cancelled_sponsorships_count) }}</span></dd></div>
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">Ended</dt><dd><span class="badge-gray">{{ number_format($donor->ended_sponsorships_count) }}</span></dd></div>
            </dl>
        </div>
    </div>

    <!-- Recent activity -->
    <div class="space-y-6">
        <div>
            <h3 class="card-title mb-4">Recent donations</h3>
            @forelse ($this->recentDonations as $donation)
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $donation->program ?: 'General donation' }}</p>
                        <p class="text-xs text-slate-400">{{ $donation->created_at->format('d M Y') }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-semibold text-slate-900">{{ number_format($donation->amount, 2) }} <span class="text-xs font-normal uppercase text-slate-400">{{ $donation->currency }}</span></p>
                        <span class="{{ $donation->status_badge }}">{{ $donation->status_label }}</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No donations yet.</p>
            @endforelse
        </div>

        <div>
            <h3 class="card-title mb-4">Recent sponsorships</h3>
            @forelse ($this->recentSponsorships as $sponsorship)
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $sponsorship->child?->name ?: 'Child not found' }}</p>
                        <p class="text-xs text-slate-400">{{ $sponsorship->sponsorship_date->format('d M Y') }} · {{ $sponsorship->frequency_label }}</p>
                    </div>
                    <span class="{{ $sponsorship->status_badge }}">{{ $sponsorship->status_label }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No sponsorships yet.</p>
            @endforelse
        </div>
    </div>
</div>
