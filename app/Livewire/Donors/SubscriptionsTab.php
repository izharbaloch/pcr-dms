<?php

namespace App\Livewire\Donors;

use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

/** The Subscriptions tab of the donor profile, filtered by status group. */
class SubscriptionsTab extends Component
{
    use WithoutUrlPagination, WithPagination;

    /** The donor's numeric `auto` key (subscriptions.pakcr_donor). */
    #[Locked]
    public int $donorKey;

    /** active | cancelled | ended */
    #[Locked]
    public string $scope = 'active';

    public function render(): View
    {
        $subscriptions = Subscription::query()
            ->select('id', 'reference', 'program', 'donation_type', 'amount', 'donation_frequency', 'currency', 'created_at', 'ends_at', 'stripe_status', 'isActive', 'isPaused', 'isCancelled')
            ->where('pakcr_donor', $this->donorKey)
            ->withStatus($this->scope)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.donors.subscriptions-tab', ['subscriptions' => $subscriptions]);
    }
}
