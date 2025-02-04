<?php

namespace App\Models;

use App\Enums\VatAmount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'insurer_id',
        'vat',
    ];

    protected $casts = [
        'vat' => VatAmount::class,
    ];

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class);
    }
}
