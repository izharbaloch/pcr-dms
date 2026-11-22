@php
    $stoppedLabel = $scope === 'cancelled' ? 'Cancelled on' : 'Ended on';
    $emptyText = match ($scope) {
        'active' => 'This donor has no active subscriptions.',
        'cancelled' => 'This donor has no cancelled subscriptions.',
        default => 'This donor has no ended subscriptions.',
    };
@endphp

<div>
    <div wire:loading.class="opacity-60" wire:target="gotoPage, previousPage, nextPage" class="table-wrap transition-opacity">
        <table class="table min-w-[52rem]">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Program</th>
                    <th>Amount</th>
                    <th>Frequency</th>
                    <th>Started</th>
                    @unless ($scope === 'active')
                        <th>{{ $stoppedLabel }}</th>
                    @endunless
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscriptions as $subscription)
                    <tr wire:key="subscription-{{ $subscription->source }}-{{ $subscription->id }}">
                        <td class="whitespace-nowrap font-mono text-xs text-slate-500">{{ $subscription->reference ?: '#'.$subscription->id }} @if ($subscription->is_manual)<span class="badge-blue ml-1 !font-sans">Manual</span>@endif</td>
                        <td>
                            {{ $subscription->program ?: '—' }}
                            @if ($subscription->donation_type)
                                <span class="block text-xs text-slate-400">{{ $subscription->donation_type }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap font-medium text-slate-900">{{ number_format($subscription->amount, 2) }} <span class="text-xs font-normal uppercase text-slate-400">{{ $subscription->currency }}</span></td>
                        <td class="whitespace-nowrap">{{ $subscription->frequency_label }}</td>
                        <td class="whitespace-nowrap">{{ $subscription->created_at?->format('d M Y') ?? '—' }}</td>
                        @unless ($scope === 'active')
                            <td class="whitespace-nowrap">{{ $subscription->ends_at?->format('d M Y') ?? '—' }}</td>
                        @endunless
                        <td><span class="{{ $subscription->status_badge }}">{{ $subscription->status_label }}</span></td>
                        <td class="text-right">
                            @php($key = ($subscription->is_manual ? 'manual:' : 'stripe:').$subscription->name)
                            @if ($capacities[$key] ?? false)
                                <a href="{{ route('sponsorships.create', ['donor' => $donorKey, 'source' => $key]) }}" wire:navigate class="btn-secondary whitespace-nowrap !px-3 !py-1.5 !text-xs">
                                    <x-icon name="heart" class="h-4 w-4" /> Add sponsorship
                                    <span class="text-slate-400">({{ $capacities[$key] }} {{ str('child')->plural($capacities[$key]) }} left)</span>
                                </a>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="!p-0">
                            <x-empty-state icon="clock" title="No subscriptions found">{{ $emptyText }}</x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-100">{{ $subscriptions->links() }}</div>
</div>
