<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Sorting and page-size state shared by the Livewire listing pages.
 * The component decides how $sortBy maps to a database column.
 */
trait WithListing
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    #[Url(except: 'created_at')]
    public string $sortBy = 'created_at';

    #[Url(except: 'desc')]
    public string $sortDirection = 'desc';

    #[Url(except: 25)]
    public int $perPage = 25;

    /** Any filter, search or page-size change starts again from page 1. */
    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        $this->sortDirection = $this->sortBy === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
        $this->resetPage();
    }

    /** Distinct non-empty values of a column, for filter dropdowns. */
    protected function distinctValues(string $model, string $column): array
    {
        return Cache::remember("options.{$model}.{$column}", now()->addHour(), fn () => $model::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all());
    }

    protected function direction(): string
    {
        return $this->sortDirection === 'asc' ? 'asc' : 'desc';
    }

    protected function pageSize(): int
    {
        return in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 25;
    }
}
