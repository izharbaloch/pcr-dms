<?php

namespace App\Models;

use App\Models\Concerns\FiltersListings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A donor's sponsorship of a child, from the legacy `sponsorships` table.
 *
 * Status lives in several columns that must be read together:
 * `isCancelled`, `hasEnded` and the `status` text.
 */
class Sponsorship extends Model
{
    use FiltersListings;

    protected $table = 'sponsorships';

    protected $guarded = [];

    /** Programs whose gifts can sponsor a child. */
    public const PROGRAMS = ['Orphan Sponsorship', 'Orphan Care Program'];

    /** What one child costs, in dollars: per month on a monthly plan, per year otherwise. */
    public const MONTHLY_COST = 40;

    public const YEARLY_COST = 480;

    public const ACTIVE = 'active';

    protected string $frequencyColumn = 'frequency';

    protected string $dateColumn = 'sponsorship_date';

    protected function casts(): array
    {
        return [
            'sponsorship_date' => 'date',
            'endedAt' => 'date',
            'end_date' => 'date',
        ];
    }

    public const STATUS_BADGES = [
        'active' => 'badge-green',
        'incomplete' => 'badge-yellow',
        'ended' => 'badge-gray',
        'cancelled' => 'badge-red',
    ];

    /** Cost of sponsoring one child for a donation frequency, in cents. */
    public static function unitCostCents(?string $frequency): int
    {
        return 100 * ($frequency === 'month' ? self::MONTHLY_COST : self::YEARLY_COST);
    }

    public static function isSponsorshipProgram(?string $program): bool
    {
        return in_array($program, self::PROGRAMS, true);
    }

    /** Sponsorships still drawing on the given plan or donation references. */
    public function scopeDrawingOn(Builder $query, string|array $names): Builder
    {
        return $query->whereIn('subscription_name', (array) $names)->where('status', self::ACTIVE);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'donor_id', 'id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    /** Amount in dollars (stored in cents). */
    protected function amount(): Attribute
    {
        return Attribute::get(fn ($value) => (float) $value / 100);
    }

    /** cancelled | ended | incomplete | active — cancelled takes priority, then ended. */
    protected function statusKey(): Attribute
    {
        return Attribute::get(function () {
            $status = strtolower((string) $this->status);

            return match (true) {
                $this->isCancelled || $status === 'cancelled' => 'cancelled',
                $this->hasEnded || $status === 'ended' => 'ended',
                $status === 'incomplete' => 'incomplete',
                default => 'active',
            };
        });
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => ucfirst($this->status_key));
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::get(fn () => self::STATUS_BADGES[$this->status_key]);
    }

    /** The day the sponsorship stopped, when recorded. */
    protected function stoppedOn(): Attribute
    {
        return Attribute::get(fn () => $this->endedAt ?? $this->end_date);
    }

    /** Currently running: not cancelled, not ended, and status active or due. */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('isCancelled', 0)
            ->where('hasEnded', 0)
            ->whereIn('status', ['active', 'Due']);
    }

    /** Not running and not cancelled: ended, expired or incomplete sponsorships. */
    public function scopeEnded(Builder $query): Builder
    {
        return $query
            ->whereNot(fn (Builder $q) => $q->cancelled())
            ->whereNot(fn (Builder $q) => $q->active());
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('isCancelled', 1)
            ->orWhere('status', 'Cancelled'));
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

    /** Match the sponsorship reference, the sponsored child, or the donor's name, email or phone. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->where('sponsorship_name', 'like', "%{$term}%")
            ->orWhereIn('donor_id', Donor::query()->select('id')->search($term))
            ->orWhereIn('child_id', Child::query()->select('id')->whereRaw("CONCAT_WS(' ', First_Name, Last_Name) LIKE ?", ["%{$term}%"]))));
    }
}
