@php
    $stoppedLabel = $scope === 'cancelled' ? 'Cancelled on' : 'Ended on';
    $emptyText = match ($scope) {
        'active' => 'This donor has no active sponsorships.',
        'cancelled' => 'This donor has no cancelled sponsorships.',
        default => 'This donor has no ended sponsorships.',
    };
@endphp

<div>
    <div wire:loading.class="opacity-60" wire:target="gotoPage, previousPage, nextPage" class="table-wrap transition-opacity">
        <table class="table min-w-[52rem]">
            <thead>
                <tr>
                    <th>Sponsorship</th>
                    <th>Sponsored child</th>
                    <th>Program</th>
                    <th>Amount</th>
                    <th>Frequency</th>
                    <th>Start date</th>
                    @unless ($scope === 'active')
                        <th>{{ $stoppedLabel }}</th>
                    @endunless
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sponsorships as $sponsorship)
                    <tr wire:key="sponsorship-{{ $sponsorship->id }}">
                        <td class="whitespace-nowrap font-mono text-xs text-slate-500">{{ $sponsorship->sponsorship_name ?: '#'.$sponsorship->id }}</td>
                        <td class="font-medium text-slate-900">{{ $sponsorship->child?->name ?: '—' }}</td>
                        <td>
                            {{ $sponsorship->sponsorshipType ?: '—' }}
                            @if ($sponsorship->type)
                                <span class="block text-xs text-slate-400">{{ $sponsorship->type }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap font-medium text-slate-900">{{ number_format($sponsorship->amount, 2) }}</td>
                        <td class="whitespace-nowrap">{{ $sponsorship->frequency_label }}</td>
                        <td class="whitespace-nowrap">{{ $sponsorship->sponsorship_date->format('d M Y') }}</td>
                        @unless ($scope === 'active')
                            <td class="whitespace-nowrap">{{ $sponsorship->stopped_on?->format('d M Y') ?? '—' }}</td>
                        @endunless
                        <td><span class="{{ $sponsorship->status_badge }}">{{ $sponsorship->status_label }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="!p-0">
                            <x-empty-state icon="users" title="No sponsorships found">{{ $emptyText }}</x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-100">{{ $sponsorships->links() }}</div>
</div>
