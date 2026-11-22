<?php

namespace App\Livewire\Donors;

use App\Livewire\Concerns\WithListing;
use App\Models\Donation;
use App\Models\Donor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    use WithListing;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $country = '';

    #[Url(except: '')]
    public string $giving = '';

    public function resetFilters(): void
    {
        $this->reset('search', 'type', 'country', 'giving');
        $this->resetPage();
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->type !== '' || $this->country !== '' || $this->giving !== '';
    }

    /** @return list<string> */
    #[Computed]
    public function countries(): array
    {
        return Donor::countryNames();
    }

    public function render(): View
    {
        $filtered = Donor::query()
            ->search($this->search)
            ->when($this->type === 'organization', fn (Builder $q) => $q->where('pakcr_donors.organization', '!=', ''))
            ->when($this->type === 'individual', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->whereNull('pakcr_donors.organization')
                ->orWhere('pakcr_donors.organization', '')))
            ->located($this->country)
            ->when($this->giving === 'donated', fn (Builder $q) => $q->whereIn('pakcr_donors.id', Donation::successful()->select('donor_id')))
            ->when($this->giving === 'none', fn (Builder $q) => $q->whereNotIn('pakcr_donors.id', Donation::successful()->select('donor_id')));

        // Count before joining the giving totals, so the (expensive) totals join runs only for the visible page.
        $total = (clone $filtered)->count();

        $donors = $filtered
            ->select(Donor::LISTING_COLUMNS)
            ->withGivingTotals()
            ->tap(fn (Builder $q) => $this->applySorting($q))
            ->paginate($this->pageSize(), total: $total);

        return view('livewire.donors.index', ['donors' => $donors])
            ->layout('layouts::app', [
                'title' => 'Donors',
                'subtitle' => 'Search and review everyone who gives to the organization.',
            ]);
    }

    private function applySorting(Builder $query): void
    {
        $direction = $this->direction();

        match ($this->sortBy) {
            'name' => $query->orderBy('pakcr_donors.first_name', $direction)->orderBy('pakcr_donors.last_name', $direction),
            'email' => $query->orderBy('pakcr_donors.email', $direction),
            'donations_count' => $query->orderByRaw("COALESCE(giving.donations_count, 0) {$direction}"),
            'donations_total' => $query->orderByRaw("COALESCE(giving.donations_total, 0) {$direction}"),
            'last_donation_at' => $query->orderBy('giving.last_donation_at', $direction),
            default => $query->orderBy('pakcr_donors.created_at', $direction),
        };

        // Stable order for pagination when the sort column has ties.
        $query->orderByDesc('pakcr_donors.auto');
    }
}
