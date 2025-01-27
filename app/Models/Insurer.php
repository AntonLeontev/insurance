<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insurer extends Model
{
    protected $fillable = [
        'name',
        'inn',
        'agency_id',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
