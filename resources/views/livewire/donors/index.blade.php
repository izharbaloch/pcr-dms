<div class="card">
    <!-- Toolbar -->
    <div class="space-y-4 border-b border-slate-100 p-5 sm:p-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="flex-1"><x-list-search placeholder="Search by name, email or phone number…" /></div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:flex lg:w-auto">
                <select wire:model.live="type" class="select lg:w-40" aria-label="Donor type">
                    <option value="">All types</option>
                    <option value="individual">Individual</option>
                    <option value="organization">Organization</option>
                </select>
                <select wire:model.live="country" class="select lg:w-44" aria-label="Country">
                    <option value="">All countries</option>
                    @foreach ($this->countries as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
                <select wire:model.live="giving" class="select col-span-2 sm:col-span-1 lg:w-44" aria-label="Giving">
                    <option value="">All donors</option>
                    <option value="donated">Has donated</option>
                    <option value="none">No donations yet</option>
                </select>
            </div>

            @if ($this->hasFilters)
                <button type="button" wire:click="resetFilters" class="btn-secondary !px-3.5 !py-2.5">
                    <x-icon name="x" class="h-4 w-4" /> Clear filters
                </button>
            @endif
        </div>
    </div>

    <!-- Table -->
    <div wire:loading.class="opacity-60" wire:target="search, type, country, giving, sort, perPage, gotoPage, previousPage, nextPage, resetFilters" class="table-wrap transition-opacity">
        <table class="table table-compact min-w-[48rem]">
            <thead>
                <tr>
                    <x-sort-header column="name" label="Donor" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-sort-header column="email" label="Contact" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <th class="hidden md:table-cell">Location</th>
                    <x-sort-header column="donations_count" label="Donations" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-sort-header column="donations_total" label="Total donated" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                    <x-sort-header column="last_donation_at" label="Last donation" :sort-by="$sortBy" :sort-direction="$sortDirection" class="hidden lg:table-cell" />
                    <x-sort-header column="created_at" label="Registered" :sort-by="$sortBy" :sort-direction="$sortDirection" class="hidden min-[1700px]:table-cell" />
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($donors as $donor)
                    <tr wire:key="donor-{{ $donor->auto }}">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700">{{ $donor->initials }}</span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('donors.show', $donor) }}" class="max-w-[12rem] truncate font-medium text-slate-900 hover:text-brand-700">{{ $donor->name }}</a>
                                        @if ($donor->is_organization)
                                            <span class="badge-blue">Organization</span>
                                        @endif
                                    </div>
                                    @if ($donor->donor_no)
                                        <span class="text-xs text-slate-400">{{ $donor->donor_no }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="block max-w-[16rem] truncate">{{ $donor->email }}</span>
                            <span class="block text-xs text-slate-400">{{ $donor->contact_no ?: '—' }}</span>
                        </td>
                        <td class="hidden md:table-cell">
                            <span class="block max-w-[10rem] truncate" title="{{ $donor->location }}">{{ $donor->area ?: '—' }}</span>
                            <span class="block max-w-[10rem] truncate text-xs text-slate-400">{{ $donor->country }}</span>
                        </td>
                        <td>{{ number_format((int) $donor->donations_count) }}</td>
                        <td class="whitespace-nowrap font-medium text-slate-900">${{ number_format($donor->total_given, 2) }}</td>
                        <td class="hidden whitespace-nowrap lg:table-cell">{{ $donor->last_donation_at?->format('d M Y') ?? '—' }}</td>
                        <td class="hidden whitespace-nowrap min-[1700px]:table-cell">{{ $donor->created_at?->format('d M Y') ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('donors.show', $donor) }}" class="btn-secondary !p-2" title="View profile" aria-label="View profile">
                                <x-icon name="eye" class="h-4 w-4" />
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="!p-0">
                            <x-empty-state icon="users" title="No donors found">
                                {{ $this->hasFilters ? 'No donor matches your search or filters. Try changing them or clear all filters.' : 'There are no donors to show yet.' }}
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

    <x-table-footer :paginator="$donors" :sizes="$this::PER_PAGE_OPTIONS" />
</div>
