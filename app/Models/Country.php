<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Reference list of countries (`country_lists`): full name and ISO code ("United States" / "US").
 * Donors store either form in their country column.
 */
class Country extends Model
{
    protected $table = 'country_lists';

    public function states(): HasMany
    {
        return $this->hasMany(State::class, 'country_id');
    }
}
