<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A recurring plan recorded by staff for offline giving (`manual_subscriptions`).
 *
 * `name` (e.g. "manual_00027") is the reference that donations and sponsorships point at.
 * `donor_id` holds the donor's string id, `pakcr_donor` the donor's numeric `auto` key.
 */
class ManualSubscription extends Model
{
    public const ACTIVE = 'active';

    protected $guarded = [];

    /** Amount in dollars (stored in cents). */
    protected function amount(): Attribute
    {
        return Attribute::get(fn ($value) => (float) $value / 100);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'subscription_id', 'name');
    }

    public const CANCELLED = 'Cancelled';

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('plan_status', self::ACTIVE);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('plan_status', self::CANCELLED);
    }

    /** Neither active nor cancelled. */
    public function scopeEnded(Builder $query): Builder
    {
        return $query->whereNotIn('plan_status', [self::ACTIVE, self::CANCELLED]);
    }

    public function scopeActiveFor(Builder $query, Donor $donor): Builder
    {
        return $query->where('donor_id', $donor->id)->where('plan_status', self::ACTIVE);
    }

    /** The next plan number, used for both the name (manual_00028) and the reference (RP00028). */
    public static function nextNumber(): int
    {
        return (int) static::max('id') + 1;
    }
}
