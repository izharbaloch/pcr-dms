<?php

namespace App\Models;

use App\Models\Concerns\FiltersListings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * A payment row in the legacy `pakcr_transactions` table.
 *
 * `id` is the payment reference (e.g. a Stripe charge id), `id2` the primary key,
 * and `amount` a string holding cents. Only "paid" and "succeeded" payments are
 * successful donations; failed, refunded and pending rows are never counted.
 */
class Donation extends Model
{
    use FiltersListings;

    protected $table = 'pakcr_transactions';

    protected $primaryKey = 'id2';

    protected $guarded = [];

    protected string $frequencyColumn = 'donation_frequency';

    protected string $dateColumn = 'created_at';

    /** Stored statuses grouped under a display status. Anything else is pending. */
    public const STATUS_GROUPS = [
        'completed' => ['paid', 'succeeded'],
        'failed' => ['failed'],
        'refunded' => ['refund', 'refunded'],
    ];

    /** Frequencies a donation can be recorded with (stored value => label). Anything but One Time is recurring. */
    public const FREQUENCIES = ['One Time' => 'One time', 'month' => 'Monthly', 'year' => 'Yearly'];

    public const RECURRING_FREQUENCIES = ['month', 'year'];

    public const TYPES = ['General', 'Sadaqa', 'Zakat'];

    public const RECEIPT_METHODS = ['Go Green', 'Paper receipt'];

    /** Methods processed online. Staff never record these by hand. */
    public const ONLINE_METHODS = ['Stripe', 'Paypal', 'Cheque', 'ACH', 'Stripe us_bank_account', 'Credit Card'];

    /** Methods that carry a cheque number. */
    public const CHEQUE_METHODS = ['Check', 'Cheque'];

    public const OTHER_METHOD = 'other';

    /** Value stored in `status` for a donation staff record as received. */
    public const RECORDED_STATUS = 'succeeded';

    public const STATUS_LABELS = [
        'completed' => 'Completed',
        'pending' => 'Pending',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];

    public const STATUS_BADGES = [
        'completed' => 'badge-green',
        'pending' => 'badge-yellow',
        'failed' => 'badge-red',
        'refunded' => 'badge-gray',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'donor_id', 'id');
    }

    /**
     * Payment methods staff can record by hand: those already in use, minus the online ones.
     *
     * @return list<string>
     */
    public static function manualMethods(): array
    {
        return Cache::remember('options.donation.manual_methods', now()->addHour(), fn () => static::query()
            ->whereNotNull('donation_method')
            ->where('donation_method', '!=', '')
            ->whereNotIn('donation_method', self::ONLINE_METHODS)
            ->distinct()
            ->orderBy('donation_method')
            ->pluck('donation_method')
            ->all());
    }

    public static function isRecurringFrequency(?string $frequency): bool
    {
        return in_array($frequency, self::RECURRING_FREQUENCIES, true);
    }

    /** Amount in dollars (stored in cents). */
    protected function amount(): Attribute
    {
        return Attribute::get(fn ($value) => (float) $value / 100);
    }

    /** completed | pending | failed | refunded */
    protected function statusGroup(): Attribute
    {
        return Attribute::get(function () {
            $status = strtolower((string) $this->status);

            foreach (self::STATUS_GROUPS as $group => $statuses) {
                if (in_array($status, $statuses, true)) {
                    return $group;
                }
            }

            return 'pending';
        });
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => self::STATUS_LABELS[$this->status_group]);
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::get(fn () => self::STATUS_BADGES[$this->status_group]);
    }

    /** Donations that were actually received. */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereIn('status', self::STATUS_GROUPS['completed']);
    }

    public function scopeWithStatus(Builder $query, ?string $group): Builder
    {
        return match (true) {
            $group === null || $group === '' => $query,
            $group === 'pending' => $query->where(fn (Builder $q) => $q
                ->whereNull('status')
                ->orWhereNotIn('status', array_merge(...array_values(self::STATUS_GROUPS)))),
            default => $query->whereIn('status', self::STATUS_GROUPS[$group] ?? []),
        };
    }

    /** Match the payment reference, or any donor whose name, email or phone matches. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->where('id', 'like', "%{$term}%")
            ->orWhereIn('donor_id', Donor::query()->select('id')->search($term))));
    }
}
