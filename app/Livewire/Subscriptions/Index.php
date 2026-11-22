<?php

namespace App\Livewire\Subscriptions;

use App\Livewire\Concerns\WithListing;
use App\Models\Subscription;
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
        return $this->distinctValues(Subscription::class, 'program');
    }

    public function render(): View
    {
        $subscriptions = Subscription::withManualPlans()
            ->with('donor:id,auto,first_name,last_name,organization,email')
            ->search($this->search)
            ->withStatus($this->status)
            ->withFrequency($this->frequency)
            ->when($this->program !== '', fn (Builder $q) => $q->where('program', $this->program))
            ->betweenDates($this->from, $this->to)
            ->tap(fn (Builder $q) => $this->applySorting($q))
            ->paginate($this->pageSize());

        return view('livewire.subscriptions.index', ['subscriptions' => $subscriptions])
            ->layout('layouts::app', [
                'title' => 'Subscriptions',
                'subtitle' => 'Every recurring giving plan, Stripe and manual, with its donor, amount and status.',
            ]);
    }

    private function applySorting(Builder $query): void
    {
        $direction = $this->direction();

        match ($this->sortBy) {
            'reference' => $query->orderBy('reference', $direction),
            'amount' => $query->orderByRaw("CAST(amount AS SIGNED) {$direction}"),
            'frequency' => $query->orderBy('donation_frequency', $direction),
            'status' => $query->orderBy('stripe_status', $direction),
            default => $query->orderBy('created_at', $direction),
        };

        $query->orderByDesc('id');
    }
}
