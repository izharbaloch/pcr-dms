<form wire:submit="save" class="card" novalidate>
    <div class="card-header">
        <div>
            <h2 class="card-title">Donor details</h2>
            <p class="card-subtitle">Fields marked <span class="text-red-600">*</span> are required.</p>
        </div>
    </div>

    <div class="card-body space-y-6">
        @error('form')
            <div class="alert-error"><x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0" /> {{ $message }}</div>
        @enderror

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="first_name">First name <span class="text-red-600">*</span></x-input-label>
                <x-text-input wire:model="first_name" id="first_name" autocomplete="off" />
                <x-input-error :messages="$errors->get('first_name')" />
            </div>
            <div>
                <x-input-label for="last_name">Last name <span class="text-red-600">*</span></x-input-label>
                <x-text-input wire:model="last_name" id="last_name" autocomplete="off" />
                <x-input-error :messages="$errors->get('last_name')" />
            </div>

            <div>
                <x-input-label for="email">Email <span class="text-red-600">*</span></x-input-label>
                <x-text-input wire:model.blur="email" id="email" type="email" autocomplete="off" />
                <x-input-error :messages="$errors->get('email')" />
                @if ($this->duplicate)
                    <div class="alert-warning mt-2">
                        <x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0" />
                        <div>
                            This email already belongs to
                            <a href="{{ route('donors.show', $this->duplicate) }}" class="font-semibold underline">{{ $this->duplicate->name }}</a> ({{ $this->duplicate->donor_no }}).
                        </div>
                    </div>
                @else
                    <button type="button" wire:click="usePlaceholderEmail" class="mt-1.5 text-xs font-medium text-ocean-700 hover:underline">The donor has no email? Use a placeholder address</button>
                @endif
            </div>
            <div>
                <x-input-label for="contact_no">Contact number <span class="text-red-600">*</span></x-input-label>
                <x-text-input wire:model="contact_no" id="contact_no" type="tel" autocomplete="off" />
                <x-input-error :messages="$errors->get('contact_no')" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="address">Address <span class="text-red-600">*</span></x-input-label>
                <x-text-input wire:model="address" id="address" autocomplete="off" />
                <x-input-error :messages="$errors->get('address')" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="address2">Address line 2</x-input-label>
                <x-text-input wire:model="address2" id="address2" autocomplete="off" />
                <x-input-error :messages="$errors->get('address2')" />
            </div>

            <div>
                <x-input-label for="country">Country <span class="text-red-600">*</span></x-input-label>
                <select wire:model.live="country" id="country" class="select">
                    <option value="">Select a country</option>
                    @foreach ($this->countries as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('country')" />
            </div>
            <div>
                <x-input-label for="state">State / province @if (count($this->states) > 0)<span class="text-red-600">*</span>@endif</x-input-label>
                @if (count($this->states) > 0)
                    <select wire:model="state" id="state" class="select">
                        <option value="">Select a state</option>
                        @foreach ($this->states as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                @else
                    <x-text-input wire:model="state" id="state" :disabled="$country === ''" placeholder="{{ $country === '' ? 'Select a country first' : '' }}" />
                @endif
                <x-input-error :messages="$errors->get('state')" />
            </div>

            <div>
                <x-input-label for="city">City <span class="text-red-600">*</span></x-input-label>
                <x-text-input wire:model="city" id="city" autocomplete="off" />
                <x-input-error :messages="$errors->get('city')" />
            </div>
            <div>
                <x-input-label for="zip_code">Zip code <span class="text-red-600">*</span></x-input-label>
                <x-text-input wire:model="zip_code" id="zip_code" autocomplete="off" />
                <x-input-error :messages="$errors->get('zip_code')" />
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 px-5 py-4 sm:px-6">
        <a href="{{ route('donors.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Save donor</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </div>
</form>
