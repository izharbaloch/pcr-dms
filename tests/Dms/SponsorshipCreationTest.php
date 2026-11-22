<?php

use App\Livewire\Sponsorships\Create;
use App\Models\Donation;
use App\Models\ManualSubscription;
use App\Models\Sponsorship;
use App\Support\Atomic;
use Livewire\Livewire;

function plan($donor, array $overrides = []): ManualSubscription
{
    return ManualSubscription::create([
        'pakcr_donor' => $donor->auto, 'donor_id' => $donor->id, 'name' => 'manual_00001', 'reference' => 'RP00001', 'plan_status' => 'active',
        'amount' => 8000, 'donation_type' => 'General', 'donation_frequency' => 'month', 'program' => 'Orphan Sponsorship', 'currency' => 'usd', ...$overrides,
    ]);
}

test('children are sponsored out of an existing plan with room left', function () {
    $donor = $this->makeDonor();
    plan($donor);
    $child = $this->makeChild();

    Livewire::test(Create::class, ['donor' => $donor->auto])
        ->set('source', 'manual:manual_00001')
        ->call('addChild', $child->id)
        ->call('save')
        ->assertHasNoErrors();

    $sponsorship = Sponsorship::sole();

    expect([$sponsorship->subscription_name, $sponsorship->subscription_id, $sponsorship->frequency])->toBe(['manual_00001', ManualSubscription::sole()->id, 'month'])
        ->and((int) ManualSubscription::sole()->unAssignedAmount)->toBe(4000)
        ->and($child->fresh()->Sponsorship_status)->toBe('Sponsored');
});

test('a plan cannot sponsor more children than its amount covers', function () {
    $donor = $this->makeDonor();
    plan($donor, ['amount' => 4000]);
    [$a, $b] = [$this->makeChild(), $this->makeChild()];

    Livewire::test(Create::class, ['donor' => $donor->auto])
        ->set('source', 'manual:manual_00001')
        ->set('selectedChildren', [$a->id, $b->id])
        ->call('save')
        ->assertHasErrors(['selected_children']);

    expect(Sponsorship::count())->toBe(0);
});

test('only the donor own plans and eligible programs are offered', function () {
    [$donor, $other] = [$this->makeDonor(), $this->makeDonor()];
    plan($other);
    plan($donor, ['name' => 'manual_00002', 'program' => 'Where Most Needed']);
    plan($donor, ['name' => 'manual_00003', 'plan_status' => 'Cancelled']);
    $child = $this->makeChild();

    Livewire::test(Create::class, ['donor' => $donor->auto])
        ->tap(fn ($component) => expect($component->instance()->sources)->toBeEmpty())
        ->set('source', 'manual:manual_00001') // belongs to the other donor
        ->set('selectedChildren', [$child->id])
        ->call('save')
        ->assertHasErrors(['source']);

    expect(Sponsorship::count())->toBe(0);
});

test('a one-time gift with room left can sponsor a child later', function () {
    $donor = $this->makeDonor();
    $child = $this->makeChild();
    $donation = Donation::create(['id' => '1-2026-abcd', 'donor_id' => $donor->id, 'donation_type' => 'General', 'program' => 'Orphan Care Program',
        'donation_method' => 'Cash', 'amount' => 48000, 'currency' => 'usd', 'status' => 'succeeded', 'donation_frequency' => 'One Time', 'created_at' => '2026-06-01']);

    Livewire::test(Create::class, ['donor' => $donor->auto])
        ->set('source', "donation:{$donation->id2}")
        ->call('addChild', $child->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Sponsorship::sole()->subscription_name)->toBe('1-2026-abcd')
        ->and($donation->fresh()->canAddSponsorship)->toBe('False');
});

test('a failed write leaves no partial records behind', function () {
    $donor = $this->makeDonor();
    $child = $this->makeChild();

    expect(fn () => Atomic::run(function (Atomic $atomic) use ($donor, $child) {
        $atomic->save(new ManualSubscription(['pakcr_donor' => $donor->auto, 'donor_id' => $donor->id, 'name' => 'manual_00009', 'plan_status' => 'active', 'amount' => 100]));
        $child->Sponsorship_status = 'Sponsored';
        $atomic->save($child);

        throw new RuntimeException('boom');
    }))->toThrow(RuntimeException::class, 'boom');

    expect(ManualSubscription::count())->toBe(0)
        ->and($child->fresh()->Sponsorship_status)->toBe('Unsponsored');
});
