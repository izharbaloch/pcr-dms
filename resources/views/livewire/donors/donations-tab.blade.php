<div>
    <div class="flex items-center justify-between gap-3 px-5 py-4 sm:px-6">
        <p class="text-sm text-slate-500">All payments made by this donor, newest first.</p>
        <select wire:model.live="status" class="select !w-40 !py-2" aria-label="Payment status">
            <option value="">All statuses</option>
            @foreach (\App\Models\Donation::STATUS_LABELS as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div wire:loading.class="opacity-60" wire:target="status, gotoPage, previousPage, nextPage" class="table-wrap transition-opacity">
        <table class="table min-w-[52rem]">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Program</th>
                    <th>Frequency</th>
                    <th>Method</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($donations as $donation)
                    <tr wire:key="donation-{{ $donation->id2 }}">
                        <td><span class="block max-w-[11rem] truncate font-mono text-xs text-slate-500" title="{{ $donation->id }}">{{ $donation->id }}</span></td>
                        <td class="whitespace-nowrap font-medium text-slate-900">{{ number_format($donation->amount, 2) }} <span class="text-xs font-normal uppercase text-slate-400">{{ $donation->currency }}</span></td>
                        <td class="whitespace-nowrap">{{ $donation->created_at->format('d M Y') }}</td>
                        <td>{{ $donation->program ?: '—' }}</td>
                        <td class="whitespace-nowrap">{{ $donation->frequency_label }}</td>
                        <td class="whitespace-nowrap">{{ $donation->donation_method ?: '—' }}</td>
                        <td><span class="{{ $donation->status_badge }}">{{ $donation->status_label }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="!p-0">
                            <x-empty-state icon="heart" title="No donations found">
                                {{ $status !== '' ? 'No donations have this status.' : 'This donor has not made any donations yet.' }}
                            </x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-100">{{ $donations->links() }}</div>
</div>
