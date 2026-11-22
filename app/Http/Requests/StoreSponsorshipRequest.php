<?php

namespace App\Http\Requests;

use App\Models\Child;
use App\Models\Donor;
use App\Services\SponsorshipService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Sponsoring children out of a donor's existing plan or one-time gift. */
class StoreSponsorshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'donor_id' => ['required', 'integer', Rule::exists('pakcr_donors', 'auto')],
            'source' => ['required', 'string'],
            'selected_children' => ['required', 'array', 'min:1'],
            'selected_children.*' => ['integer', 'distinct'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'donor_id.required' => 'Select the donor who sponsors.',
            'source.required' => 'Select the plan or gift that pays for the sponsorship.',
            'selected_children.required' => 'Select at least one child.',
            'selected_children.min' => 'Select at least one child.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['donor_id' => 'donor', 'selected_children' => 'children'];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkSourceAndChildren($validator)];
    }

    private function checkSourceAndChildren(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $service = app(SponsorshipService::class);
        $source = $service->findSource(Donor::findOrFail($this->input('donor_id')), $this->input('source'));
        $children = $this->input('selected_children');
        $capacity = $source ? $service->capacity($source) : 0;

        $problem = match (true) {
            $source === null => 'This plan or gift does not belong to the donor or cannot sponsor children.',
            count($children) > $capacity => "This plan or gift only covers {$capacity} more ".str('child')->plural($capacity).'.',
            ! Child::allAvailable($children) => 'A selected child is not available for sponsorship. Please choose again.',
            default => null,
        };

        if ($problem) {
            $validator->errors()->add($source === null ? 'source' : 'selected_children', $problem);
        }
    }
}
