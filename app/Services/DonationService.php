<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\Donor;
use App\Models\ManualSubscription;
use App\Models\Sponsorship;
use App\Support\Atomic;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Records donations that staff receive by hand.
 *
 * A one-time donation stands alone. A monthly or yearly one is part of a manual plan: it joins the plan
 * the user picked, or starts a new one. Children can be sponsored out of the same gift.
 */
class DonationService
{
    public function __construct(private SponsorshipService $sponsorships) {}

    /**
     * Records the donation, its plan and its sponsorships, all or nothing.
     *
     * @param  array{frequency: string, plan_id?: ?string, donation_type: string, program: string, donation_method: string, receipt_method: string, amount: numeric-string|int|float, donation_date: string, check_no?: ?string}  $data
     * @param  list<int>  $childIds  children to sponsor out of this gift (never together with an existing plan)
     */
    public function record(Donor $donor, array $data, array $childIds = []): Donation
    {
        return Atomic::run(function (Atomic $atomic) use ($donor, $data, $childIds) {
            $existingPlan = $this->existingPlan($donor, $data);
            $plan = $existingPlan ?? $this->newPlan($donor, $data, $atomic);

            $donation = $this->createDonation($donor, $data, $plan, $atomic);

            if ($existingPlan) {
                $this->countTowardsSponsorships($existingPlan, $donation, $atomic);
            }

            if ($childIds) {
                $this->sponsorships->sponsor($donor, $childIds, $plan ?? $donation, $atomic);
            }

            return $donation;
        });
    }

    /** The active plan the donation joins, if one was picked. It must be the donor's own. */
    private function existingPlan(Donor $donor, array $data): ?ManualSubscription
    {
        if (blank($data['plan_id'] ?? null)) {
            return null;
        }

        return ManualSubscription::activeFor($donor)->where('name', $data['plan_id'])->firstOrFail();
    }

    /** A recurring donation without a picked plan starts a new one; a one-time donation has no plan. */
    private function newPlan(Donor $donor, array $data, Atomic $atomic): ?ManualSubscription
    {
        if (! Donation::isRecurringFrequency($data['frequency'])) {
            return null;
        }

        $number = ManualSubscription::nextNumber();

        return $atomic->save(new ManualSubscription([
            'pakcr_donor' => $donor->auto,
            'donor_id' => $donor->id,
            'name' => 'manual_'.sprintf('%05d', $number),
            'reference' => 'RP'.sprintf('%05d', $number),
            'plan_status' => ManualSubscription::ACTIVE,
            'start_date' => $data['donation_date'],
            'amount' => $this->toCents($data['amount']),
            'donation_type' => $data['donation_type'],
            'donation_method' => $data['donation_method'],
            'donation_frequency' => $data['frequency'],
            'program' => $data['program'],
            'receipt_method' => $data['receipt_method'],
            'currency' => 'usd',
            'canAddSponsorship' => Sponsorship::isSponsorshipProgram($data['program']) ? 'True' : 'False',
        ]));
    }

    private function createDonation(Donor $donor, array $data, ?ManualSubscription $plan, Atomic $atomic): Donation
    {
        $cents = $this->toCents($data['amount']);

        return $atomic->save(new Donation([
            'id' => $donor->auto.'-'.now()->year.'-'.Str::random(4),
            'donor_id' => $donor->id,
            'donation_type' => $data['donation_type'],
            'program' => $data['program'],
            'donation_method' => $data['donation_method'],
            'receipt_method' => $data['receipt_method'],
            'donation_frequency' => $data['frequency'],
            'amount' => $cents,
            'created_at' => Carbon::parse($data['donation_date'])->startOfDay(),
            'check_no' => $data['check_no'] ?? null,
            'currency' => 'usd',
            'source' => 'dms',
            'status' => Donation::RECORDED_STATUS,
            'canAddSponsorship' => $cents >= Sponsorship::unitCostCents('year') && Sponsorship::isSponsorshipProgram($data['program']),
            'subscription_id' => $plan?->name,
        ]));
    }

    /** A payment on a plan that already sponsors children counts towards each of those sponsorships. */
    private function countTowardsSponsorships(ManualSubscription $plan, Donation $donation, Atomic $atomic): void
    {
        Sponsorship::drawingOn($plan->name)->where('isActive', 1)->get()
            ->each(fn (Sponsorship $sponsorship) => $this->sponsorships->recordPayment($sponsorship, $donation, $atomic));
    }

    /** Dollars to the cents the tables store, as a whole number. */
    private function toCents(string|int|float $dollars): int
    {
        return (int) round((float) $dollars * 100);
    }
}
