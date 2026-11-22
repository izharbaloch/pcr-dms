<div class="card">
    <div class="space-y-3 border-b border-slate-100 p-5 sm:p-6">
        <x-list-search placeholder="Search by donor name, email, phone or subscription reference…" />

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-[repeat(5,minmax(0,1fr))_auto]">
            <x-status-select />
            <x-frequency-select />
            <select wire:model.live="program" class="select" aria-label="Program">
                <option value="">All programs</option>
                @foreach ($this->programs as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            <x-date-range :from="$from" :to="$to" />
            @if ($this->hasFilters)
                <button type="button" wire:click="resetFilters" class="btn-secondary !px-3.5 !py-2.5">
                    <x-icon name="x" class="h-4 w-4" /> Clear
                </button>
            @endif
        </div>
    </div>

    <div wire:loading.class="opacity-60" wire:target="search, status, frequency, program, from, to, sort, perPage, gotoPage, previousPage, nextPage, resetFilters" class="table-wrap transition-opacity">
        <table class="table min-w-[60rem]">
            <thead>
                <tr>
                    <x-sort-header column="reference" label="Reference" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th>Donor</th>
                    <th class="hidden lg:table-cell">Program</th>
                    <x-sort-header column="amount" label="Amount" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-sort-header column="frequency" label="Frequency" :sort-by="$sortBy" :sort-direction="$sortDirection" class="hidden md:table-cell" />
                    <x-sort-header column="created_at" label="Started" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th class="hidden 2xl:table-cell">Ended on</th>
                    <x-sort-header column="status" label="Status" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscriptions as $subscription)
                    <tr wire:key="subscription-{{ $subscription->source }}-{{ $subscription->id }}">
                        <td class="whitespace-nowrap font-mono text-xs text-slate-500">{{ $subscription->reference ?: '#'.$subscription->id }} @if ($subscription->is_manual)<span class="badge-blue ml-1 !font-sans">Manual</span>@endif</td>
                        <td><x-donor-cell :donor="$subscription->donor" /></td>
                        <td class="hidden lg:table-cell">
                            {{ $subscription->program ?: '—' }}
                            <span class="block text-xs text-slate-400">{{ $subscription->donation_type }}</span>
                        </td>
                        <td class="whitespace-nowrap font-medium text-slate-900">{{ number_format($subscription->amount, 2) }} <span class="text-xs font-normal uppercase text-slate-400">{{ $subscription->currency }}</span></td>
                        <td class="hidden whitespace-nowrap md:table-cell">{{ $subscription->frequency_label }}</td>
                        <td class="whitespace-nowrap">{{ $subscription->created_at?->format('d M Y') ?? '—' }}</td>
                        <td class="hidden whitespace-nowrap 2xl:table-cell">{{ $subscription->ends_at?->format('d M Y') ?? '—' }}</td>
                        <td><span class="{{ $subscription->status_badge }}">{{ $subscription->status_label }}</span></td>
                        <td class="text-right">
                            @if ($subscription->donor)
                                <a href="{{ route('donors.show', ['donor' => $subscription->donor, 'tab' => 'subscriptions']) }}" class="btn-secondary whitespace-nowrap !px-3 !py-1.5 !text-xs">
                                    <x-icon name="eye" class="h-4 w-4" /> View donor
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="!p-0">
                            <x-empty-state icon="refresh" title="No subscriptions found">
                                {{ $this->hasFilters ? 'No subscription matches your search or filters. Try changing them or clear all filters.' : 'There are no subscriptions to show yet.' }}
                                @if ($this->hasFilters)
                                    <button type="button" wire:click="resetFilters" class="btn-secondary mt-5 block">Clear filters</button>
                                @endif
                            </x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-table-footer :paginator="$subscriptions" :sizes="$this::PER_PAGE_OPTIONS" />
</div>
