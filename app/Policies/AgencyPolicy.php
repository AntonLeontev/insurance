<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Agency;
use App\Models\User;

class AgencyPolicy
{
    public function viewUsers(User $user, Agency $agency): bool
    {
        return $user->agency_id === $agency->id
            && $user->role === Role::ADMIN;
    }

    public function deleteUsers(User $user, Agency $agency): bool
    {
        return $user->agency_id === $agency->id
            && $user->role === Role::ADMIN;
    }

    public function createUsers(User $user, Agency $agency): bool
    {
        return $user->agency_id === $agency->id
            && $user->role === Role::ADMIN;
    }
}
