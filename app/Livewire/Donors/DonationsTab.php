<?php

namespace App\Livewire\Donors;

use App\Models\Donation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

/** The "Donations" tab of the donor profile. */
class DonationsTab extends Component
{
    use WithoutUrlPagination, WithPagination;

    #[Locked]
    public string $donorId;

    public string $status = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $donations = Donation::query()
            ->select('id2', 'id', 'amount', 'currency', 'program', 'donation_type', 'donation_frequency', 'donation_method', 'status', 'created_at')
            ->where('donor_id', $this->donorId)
            ->withStatus($this->status)
            ->orderByDesc('created_at')
            ->orderByDesc('id2')
            ->paginate(10);

        return view('livewire.donors.donations-tab', ['donations' => $donations]);
    }
}
