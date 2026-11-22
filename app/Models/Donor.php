<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * A donor in the legacy `pakcr_donors` table.
 *
 * The primary key is the auto-increment `auto` column, but donations and
 * sponsorships point at the string `id` column (a Stripe customer id or a
 * legacy code), so relationships use `id` as their local key.
 */
class Donor extends Model
{
    protected $table = 'pakcr_donors';

    protected $primaryKey = 'auto';

    protected function casts(): array
    {
        return [
            'first_donation_at' => 'datetime',
            'last_donation_at' => 'datetime',
        ];
    }

    /** Columns needed to display a donor's name and contact details. */
    public const LISTING_COLUMNS = [
        'pakcr_donors.auto',
        'pakcr_donors.id',
        'pakcr_donors.donor_no',
        'pakcr_donors.first_name',
        'pakcr_donors.last_name',
        'pakcr_donors.organization',
        'pakcr_donors.email',
        'pakcr_donors.contact_no',
        'pakcr_donors.country',
        'pakcr_donors.state',
        'pakcr_donors.city',
        'pakcr_donors.created_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'auto';
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'donor_id', 'id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'pakcr_donor', 'auto');
    }

    public function sponsorships(): HasMany
    {
        return $this->hasMany(Sponsorship::class, 'donor_id', 'id');
    }

    /**
     * Personal name, falling back to the organization name.
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}") ?: ($this->organization ?: 'Unnamed donor'));
    }

    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $words = preg_split('/\s+/', $this->name, -1, PREG_SPLIT_NO_EMPTY);

            return mb_strtoupper(mb_substr($words[0], 0, 1).(isset($words[1]) ? mb_substr($words[1], 0, 1) : ''));
        });
    }

    protected function location(): Attribute
    {
        return Attribute::get(fn () => collect([$this->city, $this->state, $this->country])->filter()->implode(', '));
    }

    /** City and state, e.g. "Canton, Michigan". */
    protected function area(): Attribute
    {
        return Attribute::get(fn () => collect([$this->city, $this->state])->filter()->implode(', '));
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::get(fn () => collect([$this->address, $this->address2, $this->city, $this->state, $this->zip_code, $this->country])->filter()->implode(', '));
    }

    protected function averageDonation(): Attribute
    {
        return Attribute::get(fn () => $this->donations_count ? $this->total_given / $this->donations_count : 0);
    }

    protected function isOrganization(): Attribute
    {
        return Attribute::get(fn () => filled($this->organization));
    }

    /** Total of successful donations in dollars (amounts are stored in cents). */
    protected function totalGiven(): Attribute
    {
        return Attribute::get(fn () => (float) ($this->donations_total ?? 0) / 100);
    }

    /**
     * Adds successful-donation totals (donations_count, donations_total in cents,
     * first_donation_at, last_donation_at) using a single grouped sub-query, so each donor stays one row.
     */
    public function scopeWithGivingTotals(Builder $query): Builder
    {
        $giving = Donation::successful()
            ->selectRaw('donor_id, COUNT(*) AS donations_count, SUM(CAST(amount AS DECIMAL(14,2))) AS donations_total, MIN(created_at) AS first_donation_at, MAX(created_at) AS last_donation_at')
            ->groupBy('donor_id');

        return $query
            ->leftJoinSub($giving, 'giving', 'giving.donor_id', '=', 'pakcr_donors.id')
            ->addSelect([
                'giving.donations_count',
                'giving.donations_total',
                'giving.first_donation_at',
                'giving.last_donation_at',
            ]);
    }

    /**
     * Adds total, active, cancelled and ended sponsorship counts.
     */
    public function scopeWithSponsorshipCounts(Builder $query): Builder
    {
        return $query->withCount([
            'sponsorships',
            'sponsorships as active_sponsorships_count' => fn (Builder $q) => $q->active(),
            'sponsorships as cancelled_sponsorships_count' => fn (Builder $q) => $q->cancelled(),
            'sponsorships as ended_sponsorships_count' => fn (Builder $q) => $q->ended(),
        ]);
    }

    /**
     * Adds total, active, cancelled and ended subscription counts.
     */
    public function scopeWithSubscriptionCounts(Builder $query): Builder
    {
        return $query->withCount([
            'subscriptions',
            'subscriptions as active_subscriptions_count' => fn (Builder $q) => $q->active(),
            'subscriptions as cancelled_subscriptions_count' => fn (Builder $q) => $q->cancelled(),
            'subscriptions as ended_subscriptions_count' => fn (Builder $q) => $q->ended(),
        ]);
    }

    /**
     * Distinct donor countries as full names: donors record either "United States" or "US",
     * so codes are translated through the country list.
     *
     * @return list<string>
     */
    public static function countryNames(): array
    {
        return Cache::remember('options.donor.countries', now()->addHour(), function () {
            $names = Country::pluck('name', 'sortname');

            return static::query()
                ->where('country', '!=', '')
                ->distinct()
                ->pluck('country')
                ->map(fn (string $country) => $names[$country] ?? $country)
                ->unique()
                ->sort()
                ->values()
                ->all();
        });
    }

    /** Donors in a country (by name or code), and optionally in a state and city. */
    public function scopeLocated(Builder $query, ?string $country, ?string $state = null, ?string $city = null): Builder
    {
        return $query
            ->when($country, fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('pakcr_donors.country', $country)
                ->orWhereIn('pakcr_donors.country', Country::query()->select('sortname')->where('name', $country))))
            ->when($state, fn (Builder $q) => $q->where('pakcr_donors.state', $state))
            ->when($city, fn (Builder $q) => $q->where('pakcr_donors.city', $city));
    }

    /** Search by name, organization, email or phone number. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = "%{$term}%";

        return $query->where(fn (Builder $q) => $q
            ->whereRaw("CONCAT_WS(' ', pakcr_donors.first_name, pakcr_donors.last_name) LIKE ?", [$like])
            ->orWhere('pakcr_donors.organization', 'like', $like)
            ->orWhere('pakcr_donors.email', 'like', $like)
            ->orWhere('pakcr_donors.contact_no', 'like', $like));
    }
}
