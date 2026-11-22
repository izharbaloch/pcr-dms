<?php

use App\Livewire\Donors\SubscriptionsTab;
use App\Livewire\Sponsorships\Create;
use App\Livewire\Subscriptions\Index;
use App\Models\Donor;
use App\Models\ManualSubscription;
use App\Services\SponsorshipService;
use Livewire\Livewire;

function manualPlan($donor, string $name, string $status = 'active'): ManualSubscription
{
    return ManualSubscription::create([
        'pakcr_donor' => $donor->auto, 'donor_id' => $donor->id, 'name' => $name, 'reference' => 'RP'.substr($name, -5), 'plan_status' => $status,
        'amount' => 4000, 'donation_type' => 'General', 'donation_frequency' => 'month', 'program' => 'Orphan Care Program', 'currency' => 'usd',
    ]);
}

test('manual plans are listed on the subscriptions page with their status', function () {
    $donor = $this->makeDonor(['first_name' => 'Hira']);
    manualPlan($donor, 'manual_00001');
    manualPlan($donor, 'manual_00002', 'Cancelled');

    Livewire::test(Index::class)->assertSee('RP00001')->assertSee('Manual')->assertSee('Hira Khan')->assertDontSee('RP00002')
        ->set('status', 'cancelled')->assertSee('RP00002')->assertDontSee('RP00001')
        ->set('status', 'active')->set('search', 'hira')->assertSee('RP00001')
        ->set('frequency', 'Yearly')->assertDontSee('RP00001');
});

test('manual plans show on the donor profile tab and in its counts', function () {
    $donor = $this->makeDonor();
    manualPlan($donor, 'manual_00001');
    manualPlan($donor, 'manual_00002', 'Cancelled');

    Livewire::test(SubscriptionsTab::class, ['donorKey' => $donor->auto, 'scope' => 'active'])->assertSee('RP00001')->assertSee('Manual')->assertDontSee('RP00002');
    Livewire::test(SubscriptionsTab::class, ['donorKey' => $donor->auto, 'scope' => 'cancelled'])->assertSee('RP00002');

    $counted = Donor::query()->withSubscriptionCounts()->findOrFail($donor->auto);

    expect([$counted->subscriptions_count, $counted->active_subscriptions_count, $counted->cancelled_subscriptions_count, $counted->ended_subscriptions_count])->toBe([2, 1, 1, 0]);
});

test('the subscriptions tab offers Add sponsorship only for plans with room left', function () {
    $donor = $this->makeDonor();
    manualPlan($donor, 'manual_00001')->update(['program' => 'Orphan Sponsorship']);
    manualPlan($donor, 'manual_00002'); // Orphan Care Program, but already fully used below
    manualPlan($donor, 'manual_00003')->update(['program' => 'Where Most Needed']);
    $child = $this->makeChild();
    app(SponsorshipService::class)->sponsor($donor, [$child->id], ManualSubscription::where('name', 'manual_00002')->first());

    $tab = Livewire::test(SubscriptionsTab::class, ['donorKey' => $donor->auto, 'scope' => 'active']);

    expect($tab->viewData('capacities'))->toBe(['manual:manual_00001' => 1]);
    $tab->assertSee('Add sponsorship')->assertSee('source=manual%3Amanual_00001', false)->assertDontSee('manual%3Amanual_00002', false);
});

test('Add sponsorship opens the form with that plan already chosen', function () {
    $donor = $this->makeDonor();
    manualPlan($donor, 'manual_00001');
    manualPlan($donor, 'manual_00002');

    Livewire::withQueryParams(['source' => 'manual:manual_00002'])
        ->test(Create::class, ['donor' => $donor->auto])
        ->assertSet('source', 'manual:manual_00002');

    Livewire::withQueryParams(['source' => 'manual:manual_99999'])
        ->test(Create::class, ['donor' => $donor->auto])
        ->assertSet('source', '');
});
