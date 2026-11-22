@php
    $locked = $this->plan !== null;
    $recurring = \App\Models\Donation::isRecurringFrequency($frequency);
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <x-donor-banner :donor="$this->donor" />

    <form wire:submit="save" class="space-y-6" novalidate>
            @error('form')
                <div class="alert-error"><x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0" /> {{ $message }}</div>
            @enderror

            <!-- 2. Gift -->
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Donation details</h2>
                        <p class="card-subtitle">Fields marked <span class="text-red-600">*</span> are required.</p>
                    </div>
                    <span wire:loading wire:target="frequency, donorId, plan_id" class="text-xs text-slate-400">Updating…</span>
                </div>

                <div class="card-body grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="frequency">Frequency <span class="text-red-600">*</span></x-input-label>
                        <select wire:model.live="frequency" id="frequency" class="select">
                            <option value="">Select a frequency</option>
                            @foreach (\App\Models\Donation::FREQUENCIES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('frequency')" />
                    </div>

                    @if ($recurring)
                        <div>
                            <x-input-label for="plan_id">Recurring plan</x-input-label>
                            @if ($this->plans->isNotEmpty())
                                <select wire:model.live="plan_id" id="plan_id" class="select">
                                    <option value="">Start a new plan</option>
                                    @foreach ($this->plans as $plan)
                                        <option value="{{ $plan->name }}">{{ $plan->name }} · {{ $plan->program }} · ${{ number_format($plan->amount, 2) }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1.5 text-xs text-slate-500">
                                    {{ $locked ? 'This donation is added to the existing plan. Its program, amount, type and receipt are fixed.' : 'A new plan will be created for this donation.' }}
                                </p>
                            @else
                                <p class="rounded-lg border border-dashed border-slate-300 px-3.5 py-2.5 text-sm text-slate-500">
                                    This donor has no active {{ strtolower(\App\Models\Donation::FREQUENCIES[$frequency]) }} plan, so a new plan will be created.
                                </p>
                            @endif
                            <x-input-error :messages="$errors->get('plan_id')" />
                        </div>
                    @endif

                    <div>
                        <x-input-label for="program">Program <span class="text-red-600">*</span></x-input-label>
                        <select wire:model.live="program" id="program" class="select" @disabled($locked)>
                            <option value="">Select a program</option>
                            @foreach ($this->programs as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                            @if ($locked && ! in_array($program, $this->programs, true))
                                <option value="{{ $program }}">{{ $program }}</option>
                            @endif
                        </select>
                        <x-input-error :messages="$errors->get('program')" />
                    </div>

                    <div>
                        <x-input-label for="donation_type">Donation type <span class="text-red-600">*</span></x-input-label>
                        <select wire:model="donation_type" id="donation_type" class="select" @disabled($locked)>
                            <option value="">Select a type</option>
                            @foreach (\App\Models\Donation::TYPES as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('donation_type')" />
                    </div>

                    <div>
                        <x-input-label for="amount">Amount (USD) <span class="text-red-600">*</span></x-input-label>
                        <x-text-input wire:model.live.debounce.600ms="amount" id="amount" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" :disabled="$locked" />
                        <x-input-error :messages="$errors->get('amount')" />
                    </div>

                    <div>
                        <x-input-label for="donation_date">Donation date <span class="text-red-600">*</span></x-input-label>
                        <x-text-input wire:model="donation_date" id="donation_date" type="date" />
                        <x-input-error :messages="$errors->get('donation_date')" />
                    </div>

                    <div>
                        <x-input-label for="donation_method">Payment method <span class="text-red-600">*</span></x-input-label>
                        <select wire:model.live="donation_method" id="donation_method" class="select">
                            <option value="">Select a method</option>
                            @foreach ($this->methods as $method)
                                <option value="{{ $method }}">{{ $method }}</option>
                            @endforeach
                            <option value="{{ \App\Models\Donation::OTHER_METHOD }}">Other (add a new method)</option>
                        </select>
                        <x-input-error :messages="$errors->get('donation_method')" />
                    </div>

                    @if ($this->showMethodField)
                        <div>
                            <x-input-label for="other_method">New payment method <span class="text-red-600">*</span></x-input-label>
                            <x-text-input wire:model="other_method" id="other_method" maxlength="100" />
                        </div>
                    @endif

                    @if ($this->showChequeField)
                        <div>
                            <x-input-label for="check_no">Cheque number</x-input-label>
                            <x-text-input wire:model="check_no" id="check_no" />
                            <x-input-error :messages="$errors->get('check_no')" />
                        </div>
                    @endif

                    <div>
                        <x-input-label for="receipt_method">Receipt type <span class="text-red-600">*</span></x-input-label>
                        <select wire:model="receipt_method" id="receipt_method" class="select" @disabled($locked)>
                            <option value="">Select a receipt type</option>
                            @foreach (\App\Models\Donation::RECEIPT_METHODS as $receipt)
                                <option value="{{ $receipt }}">{{ $receipt }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('receipt_method')" />
                    </div>
                </div>
            </section>

            <!-- 3. Sponsorship (sponsorship programs only) -->
            @if ($this->canSponsor)
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Sponsor children (optional)</h2>
                            <p class="card-subtitle">
                                One child costs ${{ $this->childCost }} {{ $frequency === 'month' ? 'a month' : 'a year' }}. This gift covers exactly
                                {{ $this->childLimit() }} {{ str('child')->plural($this->childLimit()) }}: select that many, or none to sponsor later.
                            </p>
                        </div>
                    </div>
                    <div class="card-body">
                        <x-child-picker :chosen="$this->chosenChildren" :results="$this->childResults" :limit="$this->childLimit()" />
                        <x-input-error :messages="$errors->get('selected_children')" />
                    </div>
                </section>
            @endif

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('donors.show', $this->donor) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Record donation</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
    </form>
</div>
