<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insurer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'inn',
        'agency_id',
        'fiscal_credential_id',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function fiscalCredential(): BelongsTo
    {
        return $this->belongsTo(FiscalCredential::class);
    }
}
