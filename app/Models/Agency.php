<?php

namespace App\Models;

use App\Enums\Ffd;
use App\Enums\Sno;
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
        'receipt_email',
        'group_code',
        'ffd',
        'atol_login',
        'atol_password',
        'atol_token',
        'atol_token_expires',
    ];

    protected $casts = [
        'ffd' => Ffd::class,
        'atol_token_expires' => 'datetime',
        'sno' => Sno::class,
    ];

    protected $hidden = [
        'atol_token',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function insurers(): HasMany
    {
        return $this->hasMany(Insurer::class);
    }
}
