<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    /** @use HasFactory<\Database\Factories\AgencyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'inn',
        'sno',
        'email',
        'payment_address',
        'group_code',
        'ffd',
        'atol_login',
        'atol_password',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
