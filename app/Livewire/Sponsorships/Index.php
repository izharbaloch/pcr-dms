<?php

namespace App\Livewire\Sponsorships;

use App\Livewire\Concerns\WithListing;
use App\Models\Sponsorship;
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

    #[Url(except: 'active')]
    public string $status = 'active';

    #[Url(except: '')]
    public string $frequency = '';

    #[Url(except: '')]
    public string $program = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'frequency', 'program', 'from', 'to');
        $this->resetPage();
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->status !== 'active' || $this->frequency !== ''
            || $this->program !== '' || $this->from !== '' || $this->to !== '';
    }

    /** @return list<string> */
    #[Computed]
    public function programs(): array
    {
        return $this->distinctValues(Sponsorship::class, 'sponsorshipType');
    }

    public function render(): View
    {
        $sponsorships = Sponsorship::query()
            ->select('id', 'sponsorship_name', 'donor_id', 'child_id', 'sponsorshipType', 'type', 'amount', 'frequency', 'sponsorship_date', 'end_date', 'endedAt', 'status', 'isCancelled', 'hasEnded')
            ->with(['donor:id,auto,first_name,last_name,organization,email', 'child:id,First_Name,Last_Name'])
            ->search($this->search)
            ->withStatus($this->status)
            ->withFrequency($this->frequency)
            ->when($this->program !== '', fn (Builder $q) => $q->where('sponsorshipType', $this->program))
            ->betweenDates($this->from, $this->to)
            ->tap(fn (Builder $q) => $this->applySorting($q))
            ->paginate($this->pageSize());

        return view('livewire.sponsorships.index', ['sponsorships' => $sponsorships])
            ->layout('layouts::app', [
                'title' => 'Sponsorships',
                'subtitle' => 'Every child sponsorship, with its donor, amount and status.',
            ]);
    }

    private function applySorting(Builder $query): void
    {
        $direction = $this->direction();

        match ($this->sortBy) {
            'reference' => $query->orderBy('sponsorship_name', $direction),
            'amount' => $query->orderBy('amount', $direction),
            'frequency' => $query->orderBy('frequency', $direction),
            'status' => $query->orderBy('status', $direction),
            default => $query->orderBy('sponsorship_date', $direction),
        };

        $query->orderByDesc('id');
    }
}
