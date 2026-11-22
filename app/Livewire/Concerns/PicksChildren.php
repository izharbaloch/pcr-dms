<?php

namespace App\Livewire\Concerns;

use App\Models\Child;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

/**
 * Search-and-pick of children available for sponsorship, up to a limit the form decides.
 * The search runs in the database. Pair with <x-child-picker>.
 */
trait PicksChildren
{
    /** @var list<int> */
    public array $selectedChildren = [];

    public string $childSearch = '';

    /** How many children the form may pick right now. */
    abstract protected function childLimit(): int;

    public function addChild(int $id): void
    {
        if (count($this->selectedChildren) < $this->childLimit() && ! in_array($id, $this->selectedChildren, true)) {
            $this->selectedChildren[] = $id;
        }

        $this->childSearch = '';
        $this->resetValidation('selected_children');
    }

    public function removeChild(int $id): void
    {
        $this->selectedChildren = array_values(array_diff($this->selectedChildren, [$id]));
    }

    /** Drops picks beyond the limit, e.g. after the amount went down. */
    protected function trimChildren(): void
    {
        $this->selectedChildren = array_slice($this->selectedChildren, 0, $this->childLimit());
    }

    /** @return Collection<int, Child> */
    #[Computed]
    public function chosenChildren(): Collection
    {
        if ($this->selectedChildren === []) {
            return new Collection;
        }

        return Child::query()->forPicker()->whereKey($this->selectedChildren)->get();
    }

    /** @return Collection<int, Child> */
    #[Computed]
    public function childResults(): Collection
    {
        if (count($this->selectedChildren) >= $this->childLimit()) {
            return new Collection;
        }

        return Child::query()
            ->availableForSponsorship()
            ->search($this->childSearch)
            ->whereNotIn('osp_primary_infos.id', $this->selectedChildren)
            ->forPicker()
            ->limit(10)
            ->get();
    }
}
