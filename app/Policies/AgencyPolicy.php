<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Agency;
use App\Models\AgencyUser;
use App\Models\User;

class AgencyPolicy
{
    public function viewUsers(User $user, Agency $agency): bool
    {
        $agencyUser = AgencyUser::where('user_id', $user->id)->where('agency_id', $agency->id)->first();

        return $agencyUser !== null
            && $agencyUser->role === Role::ADMIN;
    }

    public function deleteUsers(User $user, Agency $agency): bool
    {
        $agencyUser = AgencyUser::where('user_id', $user->id)->where('agency_id', $agency->id)->first();

        return $agencyUser !== null
            && $agencyUser->role === Role::ADMIN;
    }

    public function createUsers(User $user, Agency $agency): bool
    {
        $agencyUser = AgencyUser::where('user_id', $user->id)->where('agency_id', $agency->id)->first();

        return $agencyUser !== null
            && $agencyUser->role === Role::ADMIN;
    }
}
