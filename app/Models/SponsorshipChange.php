<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** History of a sponsorship's beneficiary and term changes (the legacy `sponosorship_changes` table). */
class SponsorshipChange extends Model
{
    protected $table = 'sponosorship_changes';

    protected $guarded = [];
}
