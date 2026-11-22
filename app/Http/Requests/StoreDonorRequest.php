<?php

namespace App\Http\Requests;

use App\Models\Country;
use App\Models\State;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDonorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $countryId = Country::where('name', $this->input('country'))->value('id');
        $hasStates = $countryId && State::where('country_id', $countryId)->exists();

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:filter', 'max:255', Rule::unique('pakcr_donors', 'email')],
            'contact_no' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:65000'],
            'address2' => ['nullable', 'string', 'max:65000'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', Rule::exists('country_lists', 'name')],
            // Countries without a state list take no state.
            'state' => $hasStates
                ? ['required', Rule::exists('country_states', 'name')->where('country_id', $countryId)]
                : ['nullable', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'A donor with this email address already exists.',
            'state.exists' => 'Select a state of the chosen country.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'contact_no' => 'contact number',
            'address2' => 'address line 2',
            'zip_code' => 'zip code',
        ];
    }
}
