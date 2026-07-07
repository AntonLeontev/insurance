<?php

namespace App\Models;

use App\Enums\Ffd;
use App\Enums\Sno;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FiscalCredential extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id',
        'name',
        'is_default',
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
        'terminal',
        'password',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'ffd' => Ffd::class,
        'sno' => Sno::class,
        'atol_token_expires' => 'datetime',
    ];

    protected $hidden = [
        'atol_token',
        'atol_password',
        'password',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function insurers(): HasMany
    {
        return $this->hasMany(Insurer::class);
    }

    public function hasPaymentTerminal(): bool
    {
        return $this->terminal !== null
            && $this->terminal !== ''
            && $this->password !== null
            && $this->password !== '';
    }
}
