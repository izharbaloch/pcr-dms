<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A program donors can give to (`donation_products`). `website_name` is what donations store. */
class DonationProduct extends Model
{
    /**
     * Programs that can be given to as a recurring gift.
     * Monthly and yearly plans are limited to these (when active).
     */
    public const RECURRING_PROGRAMS = [
        'Orphan Care Program',
        'Orphan Sponsorship',
        'Technical Education Program',
        'WASH Program',
        'Where Most Needed',
    ];

    /**
     * Names of the active programs, optionally only those that support recurring gifts.
     *
     * @return list<string>
     */
    public static function names(bool $recurringOnly = false): array
    {
        return static::query()
            ->where('status', 1)
            ->when($recurringOnly, fn ($q) => $q->whereIn('website_name', self::RECURRING_PROGRAMS))
            ->orderBy('website_name')
            ->pluck('website_name')
            ->all();
    }
}
