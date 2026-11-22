<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * A sponsored child (`osp_primary_infos`). Only the name is used in the donor screens.
 */
class Child extends Model
{
    protected $table = 'osp_primary_infos';

    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->First_Name} {$this->Last_Name}"));
    }
}
