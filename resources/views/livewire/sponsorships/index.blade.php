<div class="card">
    <div class="space-y-3 border-b border-slate-100 p-5 sm:p-6">
        <x-list-search placeholder="Search by donor name, email, phone, child or sponsorship reference…" />

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
                    <th>Sponsored child</th>
                    <th class="hidden 2xl:table-cell">Program</th>
                    <x-sort-header column="amount" label="Amount" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-sort-header column="frequency" label="Frequency" :sort-by="$sortBy" :sort-direction="$sortDirection" class="hidden 2xl:table-cell" />
                    <x-sort-header column="created_at" label="Start date" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-sort-header column="status" label="Status" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sponsorships as $sponsorship)
                    <tr wire:key="sponsorship-{{ $sponsorship->id }}">
                        <td class="whitespace-nowrap font-mono text-xs text-slate-500">{{ $sponsorship->sponsorship_name ?: '#'.$sponsorship->id }}</td>
                        <td><x-donor-cell :donor="$sponsorship->donor" /></td>
                        <td class="font-medium text-slate-900">{{ $sponsorship->child?->name ?: '—' }}</td>
                        <td class="hidden 2xl:table-cell">
                            {{ $sponsorship->sponsorshipType ?: '—' }}
                            <span class="block text-xs text-slate-400">{{ $sponsorship->type }}</span>
                        </td>
                        <td class="whitespace-nowrap font-medium text-slate-900">{{ number_format($sponsorship->amount, 2) }}</td>
                        <td class="hidden whitespace-nowrap 2xl:table-cell">{{ $sponsorship->frequency_label }}</td>
                        <td class="whitespace-nowrap">{{ $sponsorship->sponsorship_date->format('d M Y') }}</td>
                        <td><span class="{{ $sponsorship->status_badge }}">{{ $sponsorship->status_label }}</span></td>
                        <td class="text-right">
                            @if ($sponsorship->donor)
                                <a href="{{ route('donors.show', ['donor' => $sponsorship->donor, 'tab' => 'sponsorships']) }}" class="btn-secondary whitespace-nowrap !px-3 !py-1.5 !text-xs">
                                    <x-icon name="eye" class="h-4 w-4" /> View donor
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="!p-0">
                            <x-empty-state icon="users" title="No sponsorships found">
                                {{ $this->hasFilters ? 'No sponsorship matches your search or filters. Try changing them or clear all filters.' : 'There are no sponsorships to show yet.' }}
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

    <x-table-footer :paginator="$sponsorships" :sizes="$this::PER_PAGE_OPTIONS" />
</div>
