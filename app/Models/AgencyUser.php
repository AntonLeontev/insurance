<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AgencyUser extends Pivot
{
    protected $table = 'agency_user';

    protected $casts = [
        'role' => Role::class,
    ];

    protected $appends = ['role_translated'];

    public function getRoleTranslatedAttribute()
    {
        return $this->role->name();
    }
}
