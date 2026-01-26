<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TbankCredentials extends Model
{
    protected $fillable = [
        'agency_id',
        'terminal',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
