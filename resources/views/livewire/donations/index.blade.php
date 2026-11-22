<div class="card">
    <div class="space-y-4 border-b border-slate-100 p-5 sm:p-6">
        <x-list-search placeholder="Search by donor name, email, phone or donation reference…" />

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <x-searchable-select name="donor" label="All donors" :selected="$this->selectedDonor?->name" :active="$optionsFor === 'donor'" :options="$this->menuOptions" />
            <x-searchable-select name="country" label="All countries" :selected="$country ?: null" :active="$optionsFor === 'country'" :options="$this->menuOptions" />
            <x-searchable-select name="state" :label="$country ? 'All states' : 'Select a country first'" :selected="$state ?: null" :active="$optionsFor === 'state'" :options="$this->menuOptions" :disabled="$country === ''" />
            <x-searchable-select name="city" :label="$state ? 'All cities' : 'Select a state first'" :selected="$city ?: null" :active="$optionsFor === 'city'" :options="$this->menuOptions" :disabled="$state === ''" />
            <x-searchable-select name="program" label="All programs" :selected="$program ?: null" :active="$optionsFor === 'program'" :options="$this->menuOptions" />
            <select wire:model.live="status" class="select" aria-label="Payment status">
                <option value="">All payment statuses</option>
                @foreach (\App\Models\Donation::STATUS_LABELS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <x-frequency-select />
            <select wire:model.live="type" class="select" aria-label="Donation type">
                <option value="">All donation types</option>
                @foreach ($this->types as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            <x-date-range :from="$from" :to="$to" />
        </div>

        <x-filter-chips :filters="$this->activeFilters" />
    </div>

    <div wire:loading.class="opacity-60" wire:target="search, status, type, frequency, from, to, sort, perPage, gotoPage, previousPage, nextPage, choose, clearFilter, resetFilters" class="table-wrap transition-opacity">
        <table class="table table-compact min-w-[60rem]">
            <thead>
                <tr>
                    <x-sort-header column="reference" label="Reference" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th>Donor</th>
                    <x-sort-header column="amount" label="Amount" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th class="hidden lg:table-cell">Program</th>
                    <x-sort-header column="type" label="Type" :sort-by="$sortBy" :sort-direction="$sortDirection" class="hidden md:table-cell" />
                    <th class="hidden md:table-cell">Location</th>
                    <x-sort-header column="frequency" label="Frequency" :sort-by="$sortBy" :sort-direction="$sortDirection" class="hidden 2xl:table-cell" />
                    <th class="hidden min-[1700px]:table-cell">Method</th>
                    <x-sort-header column="status" label="Status" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-sort-header column="created_at" label="Date" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($donations as $donation)
                    <tr wire:key="donation-{{ $donation->id2 }}">
                        <td><span class="block max-w-[6rem] truncate font-mono text-xs text-slate-500" title="{{ $donation->id }}">{{ $donation->id }}</span></td>
                        <td><x-donor-cell :donor="$donation->donor" /></td>
                        <td class="whitespace-nowrap font-medium text-slate-900">
                            {{ number_format($donation->amount, 2) }}
                            <span class="text-xs font-normal uppercase text-slate-400">{{ $donation->currency }}</span>
                            <span class="block text-xs font-normal text-slate-400 2xl:hidden">{{ $donation->frequency_label }}</span>
                        </td>
                        <td class="hidden lg:table-cell">{{ $donation->program ?: '—' }}</td>
                        <td class="hidden whitespace-nowrap md:table-cell">{{ $donation->donation_type ?: '—' }}</td>
                        <td class="hidden md:table-cell">
                            <span class="block max-w-[10rem] truncate">{{ $donation->donor?->area ?: '—' }}</span>
                            <span class="block max-w-[10rem] truncate text-xs text-slate-400">{{ $donation->donor?->country }}</span>
                        </td>
                        <td class="hidden whitespace-nowrap 2xl:table-cell">{{ $donation->frequency_label }}</td>
                        <td class="hidden whitespace-nowrap min-[1700px]:table-cell">{{ $donation->donation_method ?: '—' }}</td>
                        <td><span class="{{ $donation->status_badge }}">{{ $donation->status_label }}</span></td>
                        <td class="whitespace-nowrap">{{ $donation->created_at->format('d M Y') }}</td>
                        <td class="text-right">
                            @if ($donation->donor)
                                <a href="{{ route('donors.show', ['donor' => $donation->donor, 'tab' => 'donations']) }}" class="btn-secondary !p-2" title="View donor" aria-label="View donor">
                                    <x-icon name="eye" class="h-4 w-4" />
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="!p-0">
                            <x-empty-state icon="heart" title="No donations found">
                                {{ $this->activeFilters ? 'No donation matches your search or filters. Try changing them or clear all filters.' : 'There are no donations to show yet.' }}
                                @if ($this->activeFilters)
                                    <button type="button" wire:click="resetFilters" class="btn-secondary mt-5 block">Clear filters</button>
                                @endif
                            </x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-table-footer :paginator="$donations" :sizes="$this::PER_PAGE_OPTIONS" />
</div>
