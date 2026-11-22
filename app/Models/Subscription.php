<?php

namespace App\Models;

use App\Models\Concerns\FiltersListings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recurring giving plan (`subscriptions`), owned by a donor through `pakcr_donor`
 * (the donor's numeric `auto` key).
 *
 * The Stripe status is the source of truth and the flag columns can be out of date,
 * so statuses combine both. Every subscription is either active, cancelled or ended
 * (ended covers paused, pending and expired plans, which keep their own badge).
 */
class Subscription extends Model
{
    use FiltersListings;

    protected $table = 'subscriptions';

    protected string $frequencyColumn = 'donation_frequency';

    protected string $dateColumn = 'created_at';

    public const STATUS_LABELS = [
        'active' => 'Active',
        'paused' => 'Paused',
        'cancelled' => 'Cancelled',
        'pending' => 'Pending',
        'ended' => 'Ended',
    ];

    public const STATUS_BADGES = [
        'active' => 'badge-green',
        'paused' => 'badge-yellow',
        'cancelled' => 'badge-red',
        'pending' => 'badge-blue',
        'ended' => 'badge-gray',
    ];

    protected function casts(): array
    {
        return ['ends_at' => 'datetime'];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'pakcr_donor', 'auto');
    }

    /** Amount in dollars (stored in cents). */
    protected function amount(): Attribute
    {
        return Attribute::get(fn ($value) => (float) $value / 100);
    }

    /** cancelled | paused | active | pending | ended */
    protected function statusKey(): Attribute
    {
        return Attribute::get(function () {
            $status = strtolower((string) $this->stripe_status);

            return match (true) {
                $this->isCancelled || in_array($status, ['cancelled', 'canceled'], true) => 'cancelled',
                $status === 'active' && $this->isPaused => 'paused',
                $status === 'active' && $this->isActive => 'active',
                in_array($status, ['incomplete', 'pending', 'past_due'], true) => 'pending',
                default => 'ended',
            };
        });
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => self::STATUS_LABELS[$this->status_key]);
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::get(fn () => self::STATUS_BADGES[$this->status_key]);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('isCancelled', 1)
            ->orWhereIn('stripe_status', ['cancelled', 'canceled']));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNot(fn (Builder $q) => $q->cancelled())
            ->where('stripe_status', 'active')
            ->where('isPaused', 0)
            ->where('isActive', 1);
    }

    /** Not running and not cancelled: paused, pending, past due or expired plans. */
    public function scopeEnded(Builder $query): Builder
    {
        return $query
            ->whereNot(fn (Builder $q) => $q->cancelled())
            ->whereNot(fn (Builder $q) => $q->active());
    }

    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'active' => $query->active(),
            'cancelled' => $query->cancelled(),
            'ended' => $query->ended(),
            default => $query,
        };
    }

    /** Match the subscription reference, or the donor's name, email or phone. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->where('reference', 'like', "%{$term}%")
            ->orWhereIn('pakcr_donor', Donor::query()->select('auto')->search($term))));
    }
}
