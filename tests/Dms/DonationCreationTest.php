<?php

use App\Livewire\Donations\Create;
use App\Models\Donation;
use App\Models\ManualSubscription;
use App\Models\Sponsorship;
use App\Models\SponsorshipTransaction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/** The donation form with a donor picked and a valid one-time gift filled in; overrides change the gift. */
function donationForm($donor, array $overrides = [])
{
    return Livewire::test(Create::class, ['donor' => $donor->auto])
        ->set(['frequency' => 'One Time', 'donation_type' => 'General', 'program' => 'Where Most Needed', 'amount' => '100',
            'donation_date' => '2026-11-01', 'donation_method' => 'Cash', 'receipt_method' => 'Go Green', ...$overrides]);
}

test('a manual one-time donation is saved without a subscription', function () {
    $donor = $this->makeDonor();

    donationForm($donor, ['amount' => '125.50'])->call('save')->assertHasNoErrors();

    $donation = Donation::sole();

    expect($donation->donor_id)->toBe($donor->id)
        ->and($donation->getRawOriginal('amount'))->toBe('12550')
        ->and($donation->donation_frequency)->toBe('One Time')
        ->and([$donation->donation_type, $donation->program, $donation->donation_method, $donation->receipt_method])->toBe(['General', 'Where Most Needed', 'Cash', 'Go Green'])
        ->and([$donation->status, $donation->source, $donation->currency])->toBe(['succeeded', 'dms', 'usd'])
        ->and($donation->created_at->toDateString())->toBe('2026-11-01')
        ->and($donation->subscription_id)->toBeNull()
        ->and($donation->id)->toStartWith("{$donor->auto}-".now()->year.'-')
        ->and(ManualSubscription::count())->toBe(0);
});

test('a recurring donation starts a new manual plan', function () {
    $donor = $this->makeDonor();

    donationForm($donor, ['frequency' => 'month', 'amount' => '40'])->call('save')->assertHasNoErrors();

    $plan = ManualSubscription::sole();
    $donation = Donation::sole();

    expect($plan->name)->toBe('manual_00001')
        ->and($plan->reference)->toBe('RP00001')
        ->and([$plan->donor_id, $plan->pakcr_donor])->toBe([$donor->id, $donor->auto])
        ->and([$plan->plan_status, $plan->donation_frequency, $plan->getRawOriginal('amount'), $plan->program])->toBe(['active', 'month', '4000', 'Where Most Needed'])
        ->and($donation->subscription_id)->toBe('manual_00001')
        ->and($donation->donation_frequency)->toBe('month');
});

test('a donation can join an existing plan without creating another', function () {
    $donor = $this->makeDonor();
    donationForm($donor, ['frequency' => 'month', 'program' => 'Orphan Care Program', 'amount' => '20'])->call('save')->assertHasNoErrors();

    Livewire::test(Create::class, ['donor' => $donor->auto])
        ->set('frequency', 'month')
        // The donor's plan is preselected and fills in the fixed details.
        ->assertSet('plan_id', 'manual_00001')
        ->assertSet('program', 'Orphan Care Program')
        ->assertSet('amount', '20')
        ->set(['donation_date' => '2026-11-01', 'donation_method' => 'Cash'])
        // Values forced onto the locked fields are overridden by the plan's own.
        ->set(['amount' => '999', 'program' => 'Where Most Needed'])
        ->call('save')
        ->assertHasNoErrors();

    $latest = Donation::latest('id2')->first();

    expect(ManualSubscription::count())->toBe(1)
        ->and(Donation::where('subscription_id', 'manual_00001')->count())->toBe(2)
        ->and($latest->program)->toBe('Orphan Care Program')
        ->and($latest->getRawOriginal('amount'))->toBe('2000');
});

test('a plan of another donor cannot be used', function () {
    $owner = $this->makeDonor();
    $other = $this->makeDonor();
    donationForm($owner, ['frequency' => 'month'])->call('save');

    donationForm($other, ['frequency' => 'month', 'plan_id' => 'manual_00001'])
        ->call('save')
        ->assertHasErrors(['plan_id']);

    expect(Donation::count())->toBe(1);
});

test('a plan must match the donation frequency', function () {
    $donor = $this->makeDonor();
    donationForm($donor, ['frequency' => 'month'])->call('save');

    donationForm($donor, ['frequency' => 'year', 'plan_id' => 'manual_00001'])
        ->call('save')
        ->assertHasErrors(['plan_id']);

    expect(Donation::count())->toBe(1);
});

test('cancelled plans are not offered', function () {
    $donor = $this->makeDonor();
    donationForm($donor, ['frequency' => 'month'])->call('save');
    ManualSubscription::query()->update(['plan_status' => 'Cancelled']);

    donationForm($donor, ['frequency' => 'month'])
        ->assertSet('plan_id', '')
        ->tap(fn ($component) => expect($component->instance()->plans)->toBeEmpty());
});

test('required donation fields and program rules are validated', function () {
    $donor = $this->makeDonor();

    Livewire::test(Create::class, ['donor' => $donor->auto])->call('save')->assertHasErrors(['frequency', 'donation_type', 'program', 'donation_method', 'receipt_method', 'amount']);

    donationForm($donor, ['program' => 'Retired Program', 'amount' => '0'])->call('save')->assertHasErrors(['program', 'amount']);
    donationForm($donor, ['frequency' => 'month', 'program' => 'Education Support Program'])->call('save')->assertHasErrors(['program']);

    expect(Donation::count())->toBe(0);
});

test('a new payment method can be typed in', function () {
    $donor = $this->makeDonor();

    donationForm($donor, ['donation_method' => 'other', 'other_method' => 'Wire'])->call('save')->assertHasNoErrors();
    donationForm($donor, ['donation_method' => 'other', 'other_method' => ''])->call('save')->assertHasErrors(['donation_method']);

    expect(Donation::sole()->donation_method)->toBe('Wire');
});

test('Orphan Care one-time gift of 480 sponsors one child', function () {
    $donor = $this->makeDonor();
    $child = $this->makeChild();

    donationForm($donor, ['program' => 'Orphan Care Program', 'amount' => '480'])
        ->call('addChild', $child->id)
        ->call('save')
        ->assertHasNoErrors();

    $donation = Donation::sole();
    $sponsorship = Sponsorship::sole();

    expect([$sponsorship->child_id, $sponsorship->donor_id, $sponsorship->pkcr_id])->toBe([$child->id, $donor->id, $donor->auto])
        ->and([$sponsorship->subscription_name, $sponsorship->subscription_id])->toBe([$donation->id, $donation->id2])
        ->and([$sponsorship->frequency, $sponsorship->status, $sponsorship->commitmentType, $sponsorship->hasSubscription])->toBe(['One Time', 'active', 'Fixed Term', 0])
        ->and($sponsorship->getRawOriginal('amount'))->toBe('48000.00')
        ->and($sponsorship->sponsorship_name)->toBe('SP00001')
        ->and($sponsorship->sponsorship_date->toDateString())->toBe('2026-11-01')
        ->and($sponsorship->end_date->toDateString())->toBe('2027-11-01')
        ->and(SponsorshipTransaction::sole()->PaymentEndDate)->toBe('2027-11-01')
        ->and(DB::table('sponosorship_changes')->where('sponsorshipId', $sponsorship->id)->value('change'))->toBe('New')
        ->and(DB::table('sponsorship_logs')->where('sponsorshipId', $sponsorship->id)->value('action'))->toBe('New Sponosrship')
        ->and((int) $donation->fresh()->hasSponsorship)->toBe(1)
        ->and($donation->fresh()->canAddSponsorship)->toBe('False')
        ->and($child->fresh()->Sponsorship_status)->toBe('Sponsored')
        ->and([$child->fresh()->sponsorship_id, $child->fresh()->currentDonor, (bool) $child->fresh()->isSponsored])->toBe(['SP00001', $donor->id, true]);
});

test('a monthly gift of 80 sponsors two children out of a new plan', function () {
    $donor = $this->makeDonor();
    [$a, $b] = [$this->makeChild(), $this->makeChild()];

    donationForm($donor, ['frequency' => 'month', 'program' => 'Orphan Sponsorship', 'amount' => '80'])
        ->call('addChild', $a->id)
        ->call('addChild', $b->id)
        ->call('save')
        ->assertHasNoErrors();

    $plan = ManualSubscription::sole()->fresh();

    expect(Sponsorship::count())->toBe(2)
        ->and(Sponsorship::pluck('subscription_name')->unique()->all())->toBe(['manual_00001'])
        ->and(Sponsorship::pluck('hasSubscription')->unique()->all())->toBe([1])
        ->and(Sponsorship::first()->commitmentType)->toBe('Open Ended')
        ->and(SponsorshipTransaction::count())->toBe(2)
        ->and([$plan->hasSponsorship, $plan->canAddSponsorship, (int) $plan->unAssignedAmount])->toBe([1, 'False', 0])
        ->and(Donation::sole()->hasSponsorship)->toBe(1);
});

test('the number of children must match what the gift covers', function () {
    $donor = $this->makeDonor();
    $child = $this->makeChild();

    donationForm($donor, ['program' => 'Orphan Care Program', 'amount' => '960'])
        ->call('addChild', $child->id)
        ->call('save')
        ->assertHasErrors(['selected_children']);

    expect(Donation::count())->toBe(0)->and(Sponsorship::count())->toBe(0);
});

test('children cannot be picked for gifts that cannot sponsor', function () {
    $donor = $this->makeDonor();
    $child = $this->makeChild();

    donationForm($donor, ['program' => 'Where Most Needed', 'amount' => '480'])
        ->set('selectedChildren', [$child->id])
        ->call('save')
        ->assertHasErrors(['selected_children']);

    donationForm($donor, ['program' => 'Orphan Care Program', 'amount' => '30'])
        ->call('addChild', $child->id)
        ->assertSet('selectedChildren', []);

    expect(Sponsorship::count())->toBe(0)->and(Donation::count())->toBe(0);
});

test('an unavailable child is rejected and nothing is saved', function () {
    $donor = $this->makeDonor();
    $unavailable = [
        'unapproved' => $this->makeChild(['isApproved' => 0]),
        'sponsored' => $this->makeChild(['Sponsorship_status' => 'Sponsored']),
        'excluded cluster' => $this->makeChild(['cluster_id' => 2]),
        'class 10' => $this->makeChild(['Class' => '10']),
        'dropped out' => $this->makeChild(['Status' => 'Dropout']),
    ];

    foreach ($unavailable as $child) {
        donationForm($donor, ['program' => 'Orphan Care Program', 'amount' => '480'])
            ->set('selectedChildren', [$child->id])
            ->call('save')
            ->assertHasErrors(['selected_children']);
    }

    expect(Donation::count())->toBe(0)->and(Sponsorship::count())->toBe(0);
});

test('a child already sponsored cannot be sponsored again', function () {
    [$donor, $other] = [$this->makeDonor(), $this->makeDonor()];
    $child = $this->makeChild();
    $gift = ['program' => 'Orphan Care Program', 'amount' => '480'];

    donationForm($donor, $gift)->call('addChild', $child->id)->call('save')->assertHasNoErrors();

    donationForm($other, $gift)->set('selectedChildren', [$child->id])->call('save')->assertHasErrors(['selected_children']);

    expect(Sponsorship::count())->toBe(1)->and(Donation::count())->toBe(1);
});

test('a donation on a plan that sponsors a child counts towards that sponsorship', function () {
    $donor = $this->makeDonor();
    $child = $this->makeChild();
    donationForm($donor, ['frequency' => 'month', 'program' => 'Orphan Sponsorship', 'amount' => '40'])
        ->call('addChild', $child->id)->call('save')->assertHasNoErrors();

    donationForm($donor, ['frequency' => 'month', 'donation_date' => '2026-12-01'])
        ->assertSet('plan_id', 'manual_00001')
        ->call('save')
        ->assertHasNoErrors();

    $transactions = SponsorshipTransaction::orderBy('id')->get();

    expect(Sponsorship::count())->toBe(1)
        ->and(ManualSubscription::count())->toBe(1)
        ->and($transactions)->toHaveCount(2)
        ->and($transactions->pluck('amount')->unique()->all())->toBe(['4000'])
        ->and($transactions->last()->PaymentStartDate)->toBe('2026-12-01')
        ->and($transactions->last()->PaymentEndDate)->toBe('2027-01-01')
        ->and($transactions->pluck('transaction_id')->unique())->toHaveCount(2);
});
