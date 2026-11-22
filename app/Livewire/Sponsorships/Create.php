<?php

namespace App\Livewire\Sponsorships;

use App\Http\Requests\StoreSponsorshipRequest;
use App\Livewire\Concerns\PicksChildren;
use App\Livewire\Concerns\ValidatesWithFormRequest;
use App\Models\Donor;
use App\Services\SponsorshipService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Sponsors children out of a donor's existing recurring plan or one-time gift,
 * for gifts recorded without a sponsorship.
 */
class Create extends Component
{
    use PicksChildren, ValidatesWithFormRequest;

    /** The plan or gift that pays for the sponsorship, as a key from SponsorshipService::sources(). */
    #[Url]
    public string $source = '';

    /** The donor who sponsors: their `auto` key, from the profile the form was opened on. */
    #[Locked]
    public int $donorId;

    public function mount(int $donor): void
    {
        abort_unless(Donor::whereKey($donor)->exists(), 404);

        $this->donorId = $donor;

        // A plan from the URL (the profile's "Add sponsorship" action) must be one of the donor's sources.
        if (! $this->sources->has($this->source)) {
            $this->source = '';
        }
    }

    #[Computed]
    public function donor(): Donor
    {
        return Donor::query()->select(Donor::LISTING_COLUMNS)->findOrFail($this->donorId);
    }

    /** @return Collection<string, array{label: string, capacity: int}> */
    #[Computed]
    public function sources(): Collection
    {
        return app(SponsorshipService::class)->sources($this->donor);
    }

    /** How many children the chosen source can still sponsor. */
    protected function childLimit(): int
    {
        return $this->sources->get($this->source)['capacity'] ?? 0;
    }

    public function updatedSource(): void
    {
        $this->trimChildren();
    }

    public function save(SponsorshipService $sponsorships): void
    {
        $data = $this->validateWith(StoreSponsorshipRequest::class, [
            'donor_id' => $this->donorId,
            'source' => $this->source,
            'selected_children' => $this->selectedChildren,
        ]);

        $donor = Donor::findOrFail($this->donorId);
        $source = $sponsorships->findSource($donor, $this->source);

        try {
            $created = $sponsorships->sponsor($donor, $data['selected_children'], $source);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('form', 'The sponsorship could not be saved, and nothing was recorded. Please try again.');

            return;
        }

        session()->flash('success', sprintf('%d %s sponsored by %s (%s).', $created->count(), str('child')->plural($created->count()), $donor->name, $created->pluck('sponsorship_name')->implode(', ')));
        $this->redirectRoute('donors.show', ['donor' => $donor->auto, 'tab' => 'sponsorships'], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.sponsorships.create')->layout('layouts::app', [
            'title' => 'New sponsorship',
            'subtitle' => 'Sponsor children out of a donor\'s existing plan or one-time gift.',
            'breadcrumbs' => ['Donors' => route('donors.index'), $this->donor->name => route('donors.show', $this->donor), 'New sponsorship' => null],
        ]);
    }
}
