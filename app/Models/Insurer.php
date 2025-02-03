<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insurer extends Model
{
    use SoftDeletes;

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
