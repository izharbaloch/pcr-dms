<?php

namespace App\Http\Requests;

use App\Models\Child;
use App\Models\Donation;
use App\Models\DonationProduct;
use App\Models\Donor;
use App\Models\ManualSubscription;
use App\Models\Sponsorship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDonationRequest extends FormRequest
{
    private ?Donor $donor = null;

    private ?ManualSubscription $plan = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Two inputs are settled before validation:
     * - a donation joining a plan takes the plan's program, amount, type and receipt method;
     * - "other" as the payment method means the typed-in method.
     */
    protected function prepareForValidation(): void
    {
        $plan = $this->plan();

        $this->merge(array_filter([
            'program' => $plan?->program,
            'amount' => $plan?->amount,
            'donation_type' => $plan?->donation_type,
            'receipt_method' => $plan?->receipt_method,
            'donation_method' => $this->input('donation_method') === Donation::OTHER_METHOD
                ? trim((string) $this->input('other_method'))
                : null,
        ], fn ($value) => $value !== null));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $recurring = Donation::isRecurringFrequency($this->input('frequency'));

        return [
            'donor_id' => ['required', 'integer', Rule::exists('pakcr_donors', 'auto')],
            'frequency' => ['required', Rule::in(array_keys(Donation::FREQUENCIES))],
            'plan_id' => ['nullable', 'string'],
            'donation_type' => ['required', Rule::in(Donation::TYPES)],
            'program' => ['required', $this->plan() ? 'string' : Rule::in(DonationProduct::names(recurringOnly: $recurring))],
            'donation_method' => ['required', 'string', 'max:100'],
            'receipt_method' => ['required', Rule::in(Donation::RECEIPT_METHODS)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'donation_date' => ['required', 'date_format:Y-m-d'],
            'check_no' => ['nullable', 'string', 'max:255'],
            'selected_children' => ['nullable', 'array'],
            'selected_children.*' => ['integer', 'distinct'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'donor_id.required' => 'Select the donor who gave.',
            'donor_id.exists' => 'The selected donor could not be found.',
            'program.in' => 'This program cannot be given to with the selected frequency.',
            'donation_method.required' => 'Select a payment method, or type the new one.',
            'amount.gt' => 'The amount must be more than zero.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'donor_id' => 'donor',
            'plan_id' => 'plan',
            'donation_type' => 'donation type',
            'donation_method' => 'payment method',
            'receipt_method' => 'receipt type',
            'donation_date' => 'donation date',
            'check_no' => 'cheque number',
            'selected_children' => 'children',
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->checkPlan($validator),
            fn (Validator $validator) => $this->checkChildren($validator),
        ];
    }

    private function checkPlan(Validator $validator): void
    {
        if (blank($this->input('plan_id')) || $validator->errors()->has('donor_id')) {
            return;
        }

        if (! $this->plan()) {
            $validator->errors()->add('plan_id', 'Select one of this donor\'s active plans.');
        } elseif ($this->plan()->donation_frequency !== $this->input('frequency')) {
            $validator->errors()->add('plan_id', 'This plan gives '.strtolower(Donation::FREQUENCIES[$this->plan()->donation_frequency] ?? 'on another schedule').', so the frequency must match.');
        }
    }

    /** Children come out of this gift's own amount: as many as it covers, or none. */
    private function checkChildren(Validator $validator): void
    {
        $children = array_filter((array) $this->input('selected_children'));

        if ($children === [] || $validator->errors()->hasAny(['amount', 'frequency', 'program', 'selected_children.*'])) {
            return;
        }

        $cost = Sponsorship::unitCostCents($this->input('frequency'));
        $covered = intdiv((int) round((float) $this->input('amount') * 100), $cost);

        $problem = match (true) {
            $this->plan() !== null => 'Children cannot be added to an existing plan here. Use New sponsorship for that.',
            ! Sponsorship::isSponsorshipProgram($this->input('program')) => 'Only Orphan Sponsorship and Orphan Care Program gifts can sponsor children.',
            count($children) !== $covered => "This gift covers exactly {$covered} ".str('child')->plural($covered).'. Select that many, or none.',
            ! Child::allAvailable($children) => 'A selected child is not available for sponsorship. Please choose again.',
            default => null,
        };

        if ($problem) {
            $validator->errors()->add('selected_children', $problem);
        }
    }

    /** The donor's active plan the donation joins, if one was picked. */
    private function plan(): ?ManualSubscription
    {
        $donor = $this->donor();

        if (! $donor || blank($this->input('plan_id'))) {
            return null;
        }

        return $this->plan ??= ManualSubscription::activeFor($donor)->where('name', $this->input('plan_id'))->first();
    }

    private function donor(): ?Donor
    {
        return $this->donor ??= Donor::find($this->input('donor_id'), ['auto', 'id']);
    }
}
