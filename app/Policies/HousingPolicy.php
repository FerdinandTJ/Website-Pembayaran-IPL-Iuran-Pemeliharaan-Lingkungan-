<?php

namespace App\Policies;

use App\Models\Housing;
use App\Models\User;

class HousingPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Housing $housing): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Housing $housing): bool
    {
        return false;
    }

    public function delete(User $user, Housing $housing): bool
    {
        return false;
    }
}
