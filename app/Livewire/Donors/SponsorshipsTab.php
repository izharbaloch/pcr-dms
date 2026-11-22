<?php

namespace App\Livewire\Donors;

use App\Models\Sponsorship;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

/** The Sponsorships tab of the donor profile, filtered by status. */
class SponsorshipsTab extends Component
{
    use WithoutUrlPagination, WithPagination;

    #[Locked]
    public string $donorId;

    /** active | cancelled | ended */
    #[Locked]
    public string $scope = 'active';

    public function render(): View
    {
        $sponsorships = Sponsorship::query()
            ->select('id', 'sponsorship_name', 'child_id', 'sponsorshipType', 'type', 'amount', 'frequency', 'sponsorship_date', 'end_date', 'endedAt', 'status', 'isCancelled', 'hasEnded')
            ->with('child:id,First_Name,Last_Name')
            ->where('donor_id', $this->donorId)
            ->withStatus($this->scope)
            ->orderByDesc('sponsorship_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.donors.sponsorships-tab', ['sponsorships' => $sponsorships]);
    }
}
