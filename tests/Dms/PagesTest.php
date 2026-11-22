<?php

use App\Livewire\Donations\Create;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('the new donor page is shown inside the layout', function () {
    $this->get(route('donors.create'))->assertOk()->assertSee('New donor');
});

test('donations and sponsorships are created from the donor profile', function () {
    $donor = $this->makeDonor(['first_name' => 'Hira']);

    $this->get(route('donations.create', $donor))->assertOk()->assertSee('Hira Khan')->assertSee('Donation details');
    $this->get(route('sponsorships.create', $donor))->assertOk()->assertSee('Hira Khan');
    $this->get(route('donations.create', 999))->assertNotFound();
    $this->get(route('sponsorships.create', 999))->assertNotFound();
});

test('there is no way to start a donation or sponsorship without a donor', function () {
    $this->get('/donations/create')->assertNotFound();
    $this->get('/sponsorships/create')->assertNotFound();
});

test('the creation pages need a signed-in user', function () {
    $donor = $this->makeDonor();
    auth()->logout();

    $this->get(route('donations.create', $donor))->assertRedirect(route('login'));
});

test('the profile header offers only a new donation', function () {
    $donor = $this->makeDonor();

    $this->get(route('donors.show', $donor))->assertOk()
        ->assertSee(route('donations.create', $donor), false)
        ->assertDontSee(route('sponsorships.create', $donor), false);
});

test('the donation form does not repeat queries as it fills in', function () {
    $donor = $this->makeDonor();
    foreach (range(1, 12) as $i) {
        $this->makeChild(['First_Name' => "Child{$i}"]);
    }

    $component = Livewire::test(Create::class, ['donor' => $donor->auto])->set('frequency', 'month');

    DB::enableQueryLog();
    $component->set(['program' => 'Orphan Sponsorship', 'amount' => '120'])->assertSee('Child1');
    $queries = collect(DB::getQueryLog())->pluck('query');

    // The same statement more than a few times in one render would mean a query per row.
    expect($queries->countBy()->max())->toBeLessThanOrEqual(3)->and($queries->count())->toBeLessThan(25);
});
