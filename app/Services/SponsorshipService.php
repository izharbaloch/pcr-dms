<?php

namespace App\Services;

use App\Models\Child;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\ManualSubscription;
use App\Models\Sponsorship;
use App\Models\SponsorshipChange;
use App\Models\SponsorshipLog;
use App\Models\SponsorshipTransaction;
use App\Models\Subscription;
use App\Support\Atomic;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Sponsors children out of a recurring plan (manual or Stripe) or out of a one-time donation.
 *
 * A plan or donation can sponsor as many children as its amount covers: one child costs
 * Sponsorship::MONTHLY_COST per month on a monthly plan and Sponsorship::YEARLY_COST per year otherwise.
 * Every failure is a DomainException whose message is safe to show to the user.
 */
class SponsorshipService
{
    /** Kept as written (typo included): existing sponsorship logs and reports match on it. */
    private const LOG_ACTION = 'New Sponosrship';

    /**
     * Sponsors the children out of the plan or donation, all or nothing.
     *
     * @param  list<int>  $childIds
     * @return Collection<int, Sponsorship>
     */
    public function sponsor(Donor $donor, array $childIds, ManualSubscription|Subscription|Donation $source, ?Atomic $atomic = null): Collection
    {
        if ($atomic === null) {
            return Atomic::run(fn (Atomic $atomic) => $this->sponsor($donor, $childIds, $source, $atomic));
        }

        $this->ensureCanSponsor($childIds, $source);

        return $source instanceof Donation
            ? $this->sponsorFromDonation($donor, $childIds, $source, $atomic)
            : $this->sponsorFromPlan($donor, $childIds, $source, $atomic);
    }

    /** Counts one more payment towards a sponsorship (a new donation on a plan that already sponsors a child). */
    public function recordPayment(Sponsorship $sponsorship, Donation $payment, Atomic $atomic): void
    {
        $paidOn = Carbon::parse($payment->created_at)->startOfDay();

        $atomic->save(new SponsorshipTransaction([
            'sponsorship_id' => $sponsorship->id,
            'transaction_id' => $payment->id,
            'child_id' => $sponsorship->child_id,
            'donor_id' => $payment->donor_id,
            'currency' => $payment->currency,
            'amount' => (int) $sponsorship->getRawOriginal('amount'),
            'transaction_date' => $paidOn,
            'PaymentStartDate' => $paidOn,
            'PaymentEndDate' => $this->coverageEnd($paidOn, $sponsorship->frequency),
            'paymentMethod' => $payment->donation_method,
            'status' => $payment->status,
        ]));

        if ((int) $payment->hasSponsorship !== 1) {
            $payment->hasSponsorship = 1;
            $atomic->save($payment);
        }
    }

    /** How many more children the plan or donation can sponsor. */
    public function capacity(ManualSubscription|Subscription|Donation $source): int
    {
        $allocated = (int) Sponsorship::drawingOn($this->reference($source))->sum('amount');

        return $this->unitsLeft($source, $allocated);
    }

    /**
     * Everything a donor's children can be sponsored from, keyed by source key ("manual:manual_00012",
     * "stripe:sub_123", "donation:9926"): active plans and unspent one-time gifts of a sponsorship program.
     *
     * @return Collection<string, array{label: string, capacity: int}>
     */
    public function sources(Donor $donor): Collection
    {
        $sources = $this->sourceModels($donor);

        $allocated = Sponsorship::drawingOn($sources->map(fn ($source) => $this->reference($source))->all())
            ->groupBy('subscription_name')
            ->selectRaw('subscription_name, SUM(amount) AS total')
            ->pluck('total', 'subscription_name');

        return $sources
            ->map(fn ($source) => [
                'key' => $this->keyFor($source),
                'label' => $this->describe($source),
                'capacity' => $this->unitsLeft($source, (int) $allocated->get($this->reference($source), 0)),
            ])
            ->filter(fn (array $source) => $source['capacity'] > 0)
            ->keyBy('key')
            ->map(fn (array $source) => ['label' => $source['label'], 'capacity' => $source['capacity']]);
    }

    /**
     * How many more children each of the given active plans can sponsor, keyed like sources().
     * Only plans of a sponsorship program with room left are returned.
     *
     * @param  Collection<int, Subscription>  $plans  rows from Subscription::withManualPlans()
     * @return array<string, int>
     */
    public function planCapacities(Collection $plans): array
    {
        $plans = $plans->filter(fn (Subscription $plan) => $plan->status_key === 'active'
            && Sponsorship::isSponsorshipProgram($plan->program)
            && Donation::isRecurringFrequency($plan->donation_frequency));

        $allocated = Sponsorship::drawingOn($plans->pluck('name')->all())
            ->groupBy('subscription_name')
            ->selectRaw('subscription_name, SUM(amount) AS total')
            ->pluck('total', 'subscription_name');

        return $plans
            ->mapWithKeys(fn (Subscription $plan) => [
                ($plan->is_manual ? 'manual:' : 'stripe:').$plan->name => $this->unitsLeft($plan, (int) $allocated->get($plan->name, 0)),
            ])
            ->filter()
            ->all();
    }

    /** The source behind a key from sources(), only if it belongs to the donor. */
    public function findSource(Donor $donor, string $key): ManualSubscription|Subscription|Donation|null
    {
        return $this->sourceModels($donor)->first(fn ($source) => $this->keyFor($source) === $key);
    }

    /** @return Collection<int, ManualSubscription|Subscription|Donation> */
    private function sourceModels(Donor $donor): Collection
    {
        $recurring = Donation::RECURRING_FREQUENCIES;

        $manual = ManualSubscription::activeFor($donor)
            ->whereIn('program', Sponsorship::PROGRAMS)->whereIn('donation_frequency', $recurring)->get();

        $stripe = Subscription::query()->active()->where('pakcr_donor', $donor->auto)
            ->whereIn('program', Sponsorship::PROGRAMS)->whereIn('donation_frequency', $recurring)->get();

        $donations = Donation::query()->successful()->where('donor_id', $donor->id)
            ->whereIn('program', Sponsorship::PROGRAMS)
            ->where(fn ($q) => $q->whereNull('subscription_id')->orWhere('subscription_id', ''))
            ->where(fn ($q) => $q->whereNull('donation_frequency')->orWhereNotIn('donation_frequency', $recurring))
            ->latest()->limit(50)->get();

        return collect([...$manual, ...$stripe, ...$donations]);
    }

    private function keyFor(Model $source): string
    {
        return match (true) {
            $source instanceof Donation => "donation:{$source->id2}",
            $source instanceof ManualSubscription => "manual:{$source->name}",
            default => "stripe:{$source->name}",
        };
    }

    private function describe(Model $source): string
    {
        $amount = number_format($source->amount, 2);

        if ($source instanceof Donation) {
            return "One-time gift {$source->id} · {$source->program} · \${$amount} on {$source->created_at->format('d M Y')}";
        }

        $kind = $source instanceof ManualSubscription ? 'Manual plan' : 'Stripe plan';

        return "{$kind} {$source->name} · {$source->program} · \${$amount} ".($source->donation_frequency === 'month' ? 'monthly' : 'yearly');
    }

    /** The name sponsorships record the plan or donation under. */
    private function reference(Model $source): string
    {
        return $source instanceof Donation ? $source->id : $source->name;
    }

    private function cents(Model $source): int
    {
        return (int) $source->getRawOriginal('amount');
    }

    private function unitsLeft(Model $source, int $allocated): int
    {
        return max(0, intdiv($this->cents($source) - $allocated, Sponsorship::unitCostCents($source->donation_frequency)));
    }

    /** Fails, before anything is written, unless every child can be sponsored out of the source. */
    private function ensureCanSponsor(array $childIds, Model $source): void
    {
        if ($childIds === [] || count($childIds) !== count(array_unique($childIds))) {
            throw new DomainException('Select at least one child, each only once.');
        }

        if (! Sponsorship::isSponsorshipProgram($source->program)) {
            throw new DomainException('Only Orphan Sponsorship and Orphan Care Program gifts can sponsor a child.');
        }

        if (! Child::allAvailable($childIds)) {
            throw new DomainException('A selected child is no longer available for sponsorship. Please choose again.');
        }

        if (count($childIds) > $this->capacity($source)) {
            $what = $source instanceof Donation ? 'donation' : 'plan';

            throw new DomainException("This {$what} does not have enough remaining amount to cover this sponsorship.");
        }
    }

    /** Sponsors children out of a plan: each counts every payment already made on it. */
    private function sponsorFromPlan(Donor $donor, array $childIds, ManualSubscription|Subscription $plan, Atomic $atomic): Collection
    {
        $payments = Donation::where('subscription_id', $plan->name)->get();
        $cost = Sponsorship::unitCostCents($plan->donation_frequency);

        $sponsorships = collect($childIds)->map(function (int $childId) use ($donor, $plan, $payments, $cost, $atomic) {
            $sponsorship = $atomic->save(new Sponsorship([
                'sponsorship_name' => $this->nextName(),
                'child_id' => $childId,
                'donor_id' => $donor->id,
                'pkcr_id' => $donor->auto,
                'sponsorshipType' => $plan->program,
                'subscription_id' => $plan->id,
                'subscription_name' => $plan->name,
                'frequency' => $plan->donation_frequency,
                'type' => $plan->donation_type,
                'amount' => $cost,
                'currency' => $plan->currency ?: 'usd',
                'status' => Sponsorship::ACTIVE,
                'sponsorship_date' => $plan->created_at,
                'end_date' => $plan->ends_at,
                'hasSubscription' => 1,
                'commitmentType' => 'Open Ended',
            ]));

            $payments->each(fn (Donation $payment) => $this->recordPayment($sponsorship, $payment, $atomic));
            $this->finish($sponsorship, $donor, $plan->created_at, $atomic);

            return $sponsorship;
        });

        $allocated = (int) Sponsorship::drawingOn($plan->name)->sum('amount');

        $plan->canAddSponsorship = $allocated >= $this->cents($plan) ? 'False' : $plan->canAddSponsorship;
        $plan->hasSponsorship = 1;
        $plan->unAssignedAmount = $this->cents($plan) - $allocated;
        $atomic->save($plan);

        return $sponsorships;
    }

    /** Sponsors children out of a one-time donation, for a fixed term of one year from the donation date. */
    private function sponsorFromDonation(Donor $donor, array $childIds, Donation $donation, Atomic $atomic): Collection
    {
        $cost = Sponsorship::unitCostCents($donation->donation_frequency);
        $startsOn = Carbon::parse($donation->created_at)->startOfDay();
        $oneTime = ! Donation::isRecurringFrequency($donation->donation_frequency);

        $sponsorships = collect($childIds)->map(function (int $childId) use ($donor, $donation, $cost, $startsOn, $oneTime, $atomic) {
            $sponsorship = $atomic->save(new Sponsorship([
                'sponsorship_name' => $this->nextName(),
                'child_id' => $childId,
                'donor_id' => $donor->id,
                'pkcr_id' => $donor->auto,
                'sponsorshipType' => $donation->program,
                'subscription_id' => $donation->id2,
                'subscription_name' => $donation->id,
                'frequency' => $donation->donation_frequency,
                'type' => $donation->donation_type,
                'amount' => $cost,
                'currency' => $donation->currency,
                'status' => Sponsorship::ACTIVE,
                'sponsorship_date' => $startsOn,
                'end_date' => $startsOn->copy()->addYear(),
                'hasSubscription' => $oneTime ? 0 : 1,
                'commitmentType' => $oneTime ? 'Fixed Term' : 'Open Ended',
            ]));

            $atomic->save(new SponsorshipTransaction([
                'sponsorship_id' => $sponsorship->id,
                'transaction_id' => $donation->id,
                'child_id' => $childId,
                'donor_id' => $donor->id,
                'currency' => $donation->currency,
                'amount' => $cost,
                'transaction_date' => $startsOn,
                'PaymentStartDate' => $startsOn,
                'PaymentEndDate' => $this->coverageEnd($startsOn, $donation->donation_frequency),
                'paymentMethod' => $donation->donation_method,
                'status' => $donation->status,
            ]));

            $this->finish($sponsorship, $donor, $startsOn, $atomic);

            return $sponsorship;
        });

        $allocated = (int) Sponsorship::drawingOn($donation->id)->sum('amount');

        $donation->canAddSponsorship = $allocated >= $this->cents($donation) ? 'False' : $donation->canAddSponsorship;
        $donation->hasSponsorship = 1;
        $donation->unAssignedAmount = min($this->cents($donation) - $allocated, 999999.99);
        $atomic->save($donation);

        return $sponsorships;
    }

    /** Marks the child as sponsored and records the sponsorship's first history and log entries. */
    private function finish(Sponsorship $sponsorship, Donor $donor, CarbonInterface $sponsoredOn, Atomic $atomic): void
    {
        $child = Child::findOrFail($sponsorship->child_id);
        $child->sponsorship_id = $sponsorship->sponsorship_name;
        $child->Date_Of_Sponsorship = $sponsoredOn->toDateString();
        $child->Sponsorship_status = 'Sponsored';
        $child->isSponsored = true;
        $child->currentDonor = $donor->id;
        $atomic->save($child);

        $atomic->save(new SponsorshipChange([
            'sponsorshipId' => $sponsorship->id,
            'change' => 'New',
            'startDate' => $sponsorship->sponsorship_date,
            'endDate' => $sponsorship->end_date,
            'childId' => $sponsorship->child_id,
            'frequency' => $sponsorship->frequency,
            'donorId' => $donor->id,
            'stipulation' => $sponsorship->type,
            'currency' => $sponsorship->currency,
            'amount' => (int) $sponsorship->getRawOriginal('amount'),
        ]));

        $atomic->save(new SponsorshipLog([
            'sponsorshipId' => $sponsorship->id,
            'action' => self::LOG_ACTION,
            'userId' => (string) Auth::id(),
            'comment' => '',
        ]));
    }

    /** "SP02410": the number is one past the latest sponsorship. */
    private function nextName(): string
    {
        return 'SP'.sprintf('%05d', (int) Sponsorship::max('id') + 1);
    }

    /** The day a payment's cover ends: a month on a monthly plan, a year otherwise. */
    private function coverageEnd(CarbonInterface $from, ?string $frequency): Carbon
    {
        $end = Carbon::parse($from);

        return match ($frequency) {
            'month' => $end->addMonth(),
            'year', 'One Time' => $end->addYear(),
            default => $end,
        };
    }
}
