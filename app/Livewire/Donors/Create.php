<?php

namespace App\Livewire\Donors;

use App\Http\Requests\StoreDonorRequest;
use App\Livewire\Concerns\ValidatesWithFormRequest;
use App\Models\Country;
use App\Models\Donor;
use App\Models\State;
use App\Services\DonorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * New donor form.
 */
class Create extends Component
{
    use ValidatesWithFormRequest;

    private const FIELDS = ['first_name', 'last_name', 'email', 'contact_no', 'address', 'address2', 'city', 'country', 'state', 'zip_code'];

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $contact_no = '';

    public string $address = '';

    public string $address2 = '';

    public string $city = '';

    public string $country = '';

    public string $state = '';

    public string $zip_code = '';

    /** @return list<string> */
    #[Computed]
    public function countries(): array
    {
        return Cache::remember('options.countries', now()->addDay(), fn () => Country::orderBy('name')->pluck('name')->all());
    }

    /** @return list<string> */
    #[Computed]
    public function states(): array
    {
        return State::query()
            ->whereIn('country_id', Country::query()->select('id')->where('name', $this->country))
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /** The donor already using the typed email address, if any. */
    #[Computed]
    public function duplicate(): ?Donor
    {
        return trim($this->email) === ''
            ? null
            : Donor::query()->select(Donor::LISTING_COLUMNS)->where('email', trim($this->email))->first();
    }

    public function updatedCountry(): void
    {
        $this->reset('state');
        $this->resetValidation('state');
    }

    public function updatedEmail(): void
    {
        $this->validateFieldWith(StoreDonorRequest::class, $this->formData(), 'email');
    }

    public function usePlaceholderEmail(DonorService $donors): void
    {
        $this->email = $donors->nextPlaceholderEmail();
        $this->resetValidation('email');
    }

    public function save(DonorService $donors): void
    {
        $data = $this->validateWith(StoreDonorRequest::class, $this->formData());

        try {
            $donor = $donors->create($data);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('form', 'The donor could not be saved. Please try again.');

            return;
        }

        session()->flash('success', "Donor {$donor->name} was added as {$donor->donor_no}.");
        $this->redirectRoute('donors.show', $donor, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.donors.create')->layout('layouts::app', [
            'title' => 'New donor',
            'subtitle' => 'Add someone who gives to the organization.',
            'breadcrumbs' => ['Donors' => route('donors.index'), 'New donor' => null],
        ]);
    }

    /** @return array<string, string> */
    private function formData(): array
    {
        return $this->only(self::FIELDS);
    }
}
