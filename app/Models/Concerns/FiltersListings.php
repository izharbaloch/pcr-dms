<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Frequency and date-range filtering shared by donations, sponsorships and subscriptions.
 * The using model sets $frequencyColumn and $dateColumn.
 */
trait FiltersListings
{
    /** Stored frequencies grouped under a display frequency. Anything else is one-time. */
    public const FREQUENCY_GROUPS = [
        'Monthly' => ['month', 'monthly'],
        'Yearly' => ['year', 'yearly'],
        'Daily' => ['day', 'daily'],
    ];

    public const ONE_TIME = 'One Time';

    /** Display label for a stored frequency such as "month", "Monthly" or null. */
    public static function labelFrequency(?string $stored): string
    {
        $stored = strtolower(trim((string) $stored));

        foreach (self::FREQUENCY_GROUPS as $label => $values) {
            if (in_array($stored, $values, true)) {
                return $label;
            }
        }

        return self::ONE_TIME;
    }

    protected function frequencyLabel(): Attribute
    {
        return Attribute::get(fn () => self::labelFrequency($this->{$this->frequencyColumn}));
    }

    public function scopeWithFrequency(Builder $query, ?string $label): Builder
    {
        return match (true) {
            blank($label) => $query,
            $label === self::ONE_TIME => $query->where(fn (Builder $q) => $q
                ->whereNull($this->frequencyColumn)
                ->orWhereNotIn($this->frequencyColumn, array_merge(...array_values(self::FREQUENCY_GROUPS)))),
            default => $query->whereIn($this->frequencyColumn, self::FREQUENCY_GROUPS[$label] ?? []),
        };
    }

    /** Inclusive date range (either end may be empty). */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        $from = $from && Carbon::hasFormat($from, 'Y-m-d') ? Carbon::parse($from)->startOfDay() : null;
        $to = $to && Carbon::hasFormat($to, 'Y-m-d') ? Carbon::parse($to)->addDay()->startOfDay() : null;

        return $query
            ->when($from, fn (Builder $q) => $q->where($this->dateColumn, '>=', $from))
            ->when($to, fn (Builder $q) => $q->where($this->dateColumn, '<', $to));
    }
}
