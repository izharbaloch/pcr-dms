<?php

namespace App\Livewire\Donations;

use App\Http\Requests\StoreDonationRequest;
use App\Livewire\Concerns\PicksChildren;
use App\Livewire\Concerns\ValidatesWithFormRequest;
use App\Models\Donation;
use App\Models\DonationProduct;
use App\Models\Donor;
use App\Models\ManualSubscription;
use App\Models\Sponsorship;
use App\Services\DonationService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Records a donation received by hand: against a donor, optionally joining one of the donor's
 * manual plans, and for sponsorship programs optionally sponsoring children out of the same gift.
 */
class Create extends Component
{
    use PicksChildren, ValidatesWithFormRequest;

    private const FIELDS = ['frequency', 'plan_id', 'donation_type', 'program', 'donation_method', 'other_method', 'receipt_method', 'amount', 'donation_date', 'check_no'];

    public string $frequency = '';

    /** The plan the donation joins; empty starts a new plan (for monthly and yearly gifts). */
    public string $plan_id = '';

    public string $donation_type = '';

    public string $program = '';

    public string $donation_method = '';

    public string $other_method = '';

    public string $receipt_method = '';

    public string $amount = '';

    public string $donation_date = '';

    public string $check_no = '';

    /** The donor the donation is for: their `auto` key, from the profile the form was opened on. */
    #[Locked]
    public int $donorId;

    public function mount(int $donor): void
    {
        abort_unless(Donor::whereKey($donor)->exists(), 404);

        $this->donorId = $donor;
        $this->donation_date = today()->toDateString();
    }

    #[Computed]
    public function donor(): Donor
    {
        return Donor::query()->select(Donor::LISTING_COLUMNS)->findOrFail($this->donorId);
    }

    /** @return list<string> */
    #[Computed]
    public function programs(): array
    {
        return DonationProduct::names(recurringOnly: $this->isRecurring());
    }

    /** @return list<string> */
    #[Computed]
    public function methods(): array
    {
        return Donation::manualMethods();
    }

    /** The donor's active plans that give on the chosen schedule, newest first, keyed by plan name. */
    #[Computed]
    public function plans(): Collection
    {
        if (! $this->isRecurring()) {
            return collect();
        }

        return ManualSubscription::query()
            ->where('pakcr_donor', $this->donorId)
            ->where('plan_status', ManualSubscription::ACTIVE)
            ->where('donation_frequency', $this->frequency)
            ->latest('id')
            ->get(['id', 'name', 'program', 'amount', 'donation_type', 'receipt_method', 'donation_frequency'])
            ->keyBy('name');
    }

    /** The plan the donation joins; its program, amount, type and receipt method are fixed. */
    #[Computed]
    public function plan(): ?ManualSubscription
    {
        return $this->plans->get($this->plan_id);
    }

    #[Computed]
    public function showMethodField(): bool
    {
        return $this->donation_method === Donation::OTHER_METHOD;
    }

    #[Computed]
    public function showChequeField(): bool
    {
        return in_array($this->donation_method, Donation::CHEQUE_METHODS, true);
    }

    /** Whether children can be sponsored out of this gift: a sponsorship program, a new plan or one-time, and enough money for one child. */
    #[Computed]
    public function canSponsor(): bool
    {
        return $this->plan === null
            && Sponsorship::isSponsorshipProgram($this->program)
            && $this->childLimit() > 0;
    }

    /** One child costs this many dollars on the chosen schedule. */
    #[Computed]
    public function childCost(): int
    {
        return $this->frequency === 'month' ? Sponsorship::MONTHLY_COST : Sponsorship::YEARLY_COST;
    }

    /** The gift covers this many children; the user picks exactly that many, or none. */
    protected function childLimit(): int
    {
        if ($this->plan || $this->frequency === '' || ! is_numeric($this->amount)) {
            return 0;
        }

        return intdiv((int) round((float) $this->amount * 100), Sponsorship::unitCostCents($this->frequency));
    }

    public function updatedFrequency(): void
    {
        $this->reset('plan_id', 'selectedChildren');

        if ($this->program !== '' && ! in_array($this->program, $this->programs, true)) {
            $this->program = '';
        }

        $this->selectFirstPlan();
        $this->resetValidation();
    }

    public function updatedPlanId(): void
    {
        $this->applyPlan();
        $this->selectedChildren = [];
    }

    public function updatedProgram(): void
    {
        $this->trimChildren();
    }

    public function updatedAmount(): void
    {
        $this->trimChildren();
    }

    public function save(DonationService $donations): void
    {
        $data = $this->validateWith(StoreDonationRequest::class, $this->formData());
        $donor = Donor::findOrFail($this->donorId);

        try {
            $donation = $donations->record($donor, $data, $data['selected_children'] ?? []);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('form', 'The donation could not be saved, and nothing was recorded. Please try again.');

            return;
        }

        $sponsored = count($data['selected_children'] ?? []);
        session()->flash('success', sprintf(
            'Donation %s of $%s recorded for %s%s.',
            $donation->id,
            number_format((float) $data['amount'], 2),
            $donor->name,
            $sponsored ? " and {$sponsored} ".str('child')->plural($sponsored).' sponsored' : '',
        ));

        $this->redirectRoute('donors.show', ['donor' => $donor->auto, 'tab' => 'donations'], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.donations.create')->layout('layouts::app', [
            'title' => 'New donation',
            'subtitle' => 'Record a donation received by cash, cheque, bank transfer or another offline method.',
            'breadcrumbs' => ['Donors' => route('donors.index'), $this->donor->name => route('donors.show', $this->donor), 'New donation' => null],
        ]);
    }

    private function isRecurring(): bool
    {
        return Donation::isRecurringFrequency($this->frequency);
    }

    /** A recurring gift joins the donor's first matching plan by default; "new plan" is one click away. */
    private function selectFirstPlan(): void
    {
        $this->plan_id = (string) $this->plans->keys()->first();
        $this->applyPlan();
    }

    /** Joining a plan copies the plan's fixed details into the form. */
    private function applyPlan(): void
    {
        if (! $this->plan) {
            return;
        }

        $this->program = $this->plan->program;
        $this->amount = (string) $this->plan->amount;
        $this->donation_type = (string) ($this->plan->donation_type ?: $this->donation_type);
        $this->receipt_method = (string) ($this->plan->receipt_method ?: $this->receipt_method);
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            ...$this->only(self::FIELDS),
            'donor_id' => $this->donorId,
            'selected_children' => $this->selectedChildren,
        ];
    }
}
