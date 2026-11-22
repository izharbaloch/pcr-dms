<?php

namespace App\Livewire\Donations;

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

    /** Filters that can be cleared one at a time. */
    private const FILTERS = ['search', 'donor', 'country', 'state', 'city', 'program', 'status', 'type', 'frequency', 'from', 'to'];

    /** Filters that are cleared together with the filter they depend on. */
    private const DEPENDENTS = ['country' => ['state', 'city'], 'state' => ['city']];

    /** Searchable dropdowns. */
    private const MENUS = ['donor', 'country', 'state', 'city', 'program'];

    #[Url(except: '')]
    public string $search = '';

    /** Selected donor (pakcr_donors.auto). */
    #[Url]
    public ?int $donor = null;

    #[Url(except: '')]
    public string $country = '';

    #[Url(except: '')]
    public string $state = '';

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: '')]
    public string $program = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $frequency = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    /** The dropdown whose options are loaded, and what was typed into its search box (open/close is Alpine). */
    public ?string $optionsFor = null;

    public string $optionSearch = '';

    public function showOptions(string $menu): void
    {
        $this->optionsFor = $menu;
        $this->optionSearch = '';
    }

    public function choose(string $menu, string $value): void
    {
        abort_unless(in_array($menu, self::MENUS, true), 404);

        $this->clearFilter($menu);
        $this->{$menu} = $value;
        $this->optionsFor = null;
    }

    public function clearFilter(string $filter): void
    {
        abort_unless(in_array($filter, self::FILTERS, true), 404);

        $this->reset($filter, ...self::DEPENDENTS[$filter] ?? []);
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(self::FILTERS);
        $this->resetPage();
    }

    /** @return array<string, string> Active filters as filter => display value. */
    #[Computed]
    public function activeFilters(): array
    {
        return array_filter([
            'search' => $this->search,
            'donor' => $this->selectedDonor?->name,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'program' => $this->program,
            'status' => Donation::STATUS_LABELS[$this->status] ?? '',
            'frequency' => $this->frequency,
            'type' => $this->type,
            'from' => $this->from,
            'to' => $this->to,
        ]);
    }

    #[Computed]
    public function selectedDonor(): ?Donor
    {
        return $this->donor
            ? Donor::query()->select('auto', 'first_name', 'last_name', 'organization')->find($this->donor)
            : null;
    }

    /** @return array<string|int, string> Options (value => label) of the dropdown being used. */
    #[Computed]
    public function menuOptions(): array
    {
        $term = trim($this->optionSearch);

        return match ($this->optionsFor) {
            'donor' => $this->donorOptions($term),
            'country' => $this->matching(Donor::countryNames(), $term),
            'state' => $this->locationOptions('state', $term),
            'city' => $this->locationOptions('city', $term),
            'program' => $this->matching($this->distinctValues(Donation::class, 'program'), $term),
            default => [],
        };
    }

    /** @return list<string> */
    #[Computed]
    public function types(): array
    {
        return $this->distinctValues(Donation::class, 'donation_type');
    }

    public function render(): View
    {
        $donations = Donation::query()
            ->select('id2', 'id', 'donor_id', 'amount', 'currency', 'program', 'donation_type', 'donation_frequency', 'donation_method', 'status', 'created_at')
            ->with('donor:id,auto,first_name,last_name,organization,email,country,state,city')
            ->search($this->search)
            ->tap(fn (Builder $q) => $this->applyDonorFilters($q))
            ->when($this->program !== '', fn (Builder $q) => $q->where('program', $this->program))
            ->withStatus($this->status)
            ->withFrequency($this->frequency)
            ->when($this->type !== '', fn (Builder $q) => $q->where('donation_type', $this->type))
            ->betweenDates($this->from, $this->to)
            ->tap(fn (Builder $q) => $this->applySorting($q))
            ->paginate($this->pageSize());

        return view('livewire.donations.index', ['donations' => $donations])
            ->layout('layouts::app', [
                'title' => 'Donations',
                'subtitle' => 'Every payment received, with its status, program and payment method.',
            ]);
    }

    /** The donor and location filters both narrow down donors, so they share one sub-query. */
    private function applyDonorFilters(Builder $query): void
    {
        $donors = Donor::query()
            ->select('id')
            ->when($this->donor, fn (Builder $q) => $q->whereKey($this->donor))
            ->located($this->country, $this->state, $this->city);

        $query->when($this->donor || $this->country !== '', fn (Builder $q) => $q->whereIn('donor_id', $donors));
    }

    private function applySorting(Builder $query): void
    {
        $direction = $this->direction();

        match ($this->sortBy) {
            'reference' => $query->orderBy('id', $direction),
            'amount' => $query->orderByRaw("CAST(amount AS SIGNED) {$direction}"),
            'type' => $query->orderBy('donation_type', $direction),
            'frequency' => $query->orderBy('donation_frequency', $direction),
            'status' => $query->orderBy('status', $direction),
            default => $query->orderBy('created_at', $direction),
        };

        // Stable order for pagination when the sort column has ties.
        $query->orderByDesc('id2');
    }

    /** Latest donors matching the search, labelled "name · email". */
    private function donorOptions(string $term): array
    {
        return Donor::query()
            ->select('auto', 'first_name', 'last_name', 'organization', 'email')
            ->search($term)
            ->latest()
            ->limit(15)
            ->get()
            ->mapWithKeys(fn (Donor $donor) => [$donor->auto => "{$donor->name} · {$donor->email}"])
            ->all();
    }

    /** States of the selected country, or cities of the selected state, as donors have recorded them. */
    private function locationOptions(string $column, string $term): array
    {
        $values = Donor::query()
            ->located($this->country, $column === 'city' ? $this->state : null)
            ->where($column, 'like', "%{$term}%")
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->limit(50)
            ->pluck($column)
            ->all();

        return array_combine($values, $values);
    }

    /** Values containing the search term, as value => value. */
    private function matching(array $values, string $term): array
    {
        $matches = array_filter($values, fn (string $value) => $term === '' || stripos($value, $term) !== false);

        return array_combine($matches, $matches);
    }
}
