<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    /** @use HasFactory<\Database\Factories\AgencyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'inn',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function insurers(): HasMany
    {
        return $this->hasMany(Insurer::class);
    }

    public function fiscalCredentials(): HasMany
    {
        return $this->hasMany(FiscalCredential::class);
    }

    public function defaultFiscalCredential(): ?FiscalCredential
    {
        return $this->fiscalCredentials()->where('is_default', true)->first();
    }
}
