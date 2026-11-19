<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'agency_id',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'role' => Role::class,
        ];
    }

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class)
            ->using(AgencyUser::class)
            ->withPivot('role');
    }

    public function canViewDashboard(): bool
    {
        return $this->email === config('services.dashboard.owner_email');
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontendArray(): array
    {
        $this->loadMissing('agencies');

        return [
            ...$this->toArray(),
            'dashboard_visible' => $this->canViewDashboard(),
        ];
    }
}
