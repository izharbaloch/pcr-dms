<?php

use App\Livewire\Donors\Create;
use App\Models\Donor;
use Livewire\Livewire;

function donorForm(array $overrides = []): array
{
    return [
        'first_name' => 'Sara', 'last_name' => 'Ahmed', 'email' => 'sara@example.com', 'contact_no' => '555-0100',
        'address' => '12 Elm St', 'address2' => '', 'city' => 'Austin', 'country' => 'United States', 'state' => 'Texas',
        'zip_code' => '73301', ...$overrides,
    ];
}

test('a donor is created with the next id and a donor number', function () {
    $this->makeDonor(['email' => 'first@example.com']);

    Livewire::test(Create::class)
        ->set(donorForm())
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('donors.show', ['donor' => 2]));

    $donor = Donor::where('email', 'sara@example.com')->firstOrFail();

    expect($donor->id)->toBe('2')
        ->and($donor->auto)->toBe(2)
        ->and($donor->donor_no)->toBe(now()->year.'/US/0002')
        ->and([$donor->first_name, $donor->last_name, $donor->state, $donor->country])->toBe(['Sara', 'Ahmed', 'Texas', 'United States']);
});

test('required fields are validated', function () {
    Livewire::test(Create::class)
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name', 'email', 'contact_no', 'address', 'city', 'country', 'zip_code']);

    expect(Donor::count())->toBe(0);
});

test('the state must belong to the chosen country', function () {
    Livewire::test(Create::class)
        ->set(donorForm(['state' => 'Punjab']))
        ->call('save')
        ->assertHasErrors(['state']);
});

test('a country without a state list needs no state', function () {
    Livewire::test(Create::class)
        ->set(donorForm(['country' => 'Pakistan', 'state' => '']))
        ->call('save')
        ->assertHasNoErrors();

    expect(Donor::first()->donor_no)->toBe(now()->year.'/PK/0001');
});

test('a duplicate email is rejected and the existing donor is shown', function () {
    $existing = $this->makeDonor(['email' => 'sara@example.com']);

    Livewire::test(Create::class)
        ->set(donorForm())
        ->assertHasErrors(['email'])
        ->call('save')
        ->assertHasErrors(['email' => 'A donor with this email address already exists.'])
        ->assertSee($existing->donor_no);

    expect(Donor::count())->toBe(1);
});

test('a placeholder email is offered for donors without one', function () {
    $this->makeDonor(['email' => 'donor-7@pakcr.org']);

    Livewire::test(Create::class)->call('usePlaceholderEmail')->assertSet('email', 'donor-8@pakcr.org');
});
