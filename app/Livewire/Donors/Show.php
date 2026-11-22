<?php

namespace App\Livewire\Donors;

use App\Models\Donation;
use App\Models\Donor;
use App\Models\Sponsorship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class Show extends Component
{
    public const TABS = ['overview', 'donations', 'sponsorships', 'subscriptions'];

    /** Status sub-tabs available inside the tabs that have them. */
    public const STATUSES = [
        'sponsorships' => ['active', 'cancelled', 'ended'],
        'subscriptions' => ['active', 'cancelled', 'ended'],
    ];

    #[Locked]
    public int $donorId;

    #[Url(as: 'tab', except: 'overview')]
    public string $tab = 'overview';

    #[Url(except: 'active')]
    public string $status = 'active';

    public function mount(int $donor): void
    {
        abort_unless(Donor::whereKey($donor)->exists(), 404);

        $this->donorId = $donor;
        $this->setStatus($this->status);
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'overview';
        $this->status = 'active';
    }

    public function setStatus(string $status): void
    {
        $this->status = in_array($status, self::STATUSES[$this->tab] ?? [], true) ? $status : 'active';
    }

    /** The donor with giving totals and sponsorship / subscription counts: the source for the header, cards and tab badges. */
    #[Computed]
    public function donor(): Donor
    {
        return Donor::query()
            ->select([
                ...Donor::LISTING_COLUMNS,
                'pakcr_donors.address',
                'pakcr_donors.address2',
                'pakcr_donors.zip_code',
                'pakcr_donors.newsletter_subscribed',
            ])
            ->withGivingTotals()
            ->withSponsorshipCounts()
            ->withSubscriptionCounts()
            ->findOrFail($this->donorId);
    }

    /** @return Collection<int, Donation> */
    #[Computed]
    public function recentDonations(): Collection
    {
        return Donation::query()
            ->select('id2', 'id', 'amount', 'currency', 'program', 'status', 'created_at')
            ->where('donor_id', $this->donor->id)
            ->latest()
            ->limit(5)
            ->get();
    }

    /** @return Collection<int, Sponsorship> */
    #[Computed]
    public function recentSponsorships(): Collection
    {
        return Sponsorship::query()
            ->select('id', 'sponsorship_name', 'child_id', 'amount', 'frequency', 'status', 'isCancelled', 'hasEnded', 'sponsorship_date')
            ->with('child:id,First_Name,Last_Name')
            ->where('donor_id', $this->donor->id)
            ->latest('sponsorship_date')
            ->limit(5)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.donors.show')->layout('layouts::app', [
            'title' => 'Donor Profile',
            'breadcrumbs' => ['Donors' => route('donors.index'), $this->donor->name => null],
        ]);
    }
}
