<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $actor, User $target): bool
    {
        if ($actor->isAdmin()) {
            return $target->isManager();
        }

        return $actor->isManager()
            && $target->isResident()
            && $actor->housing_id === $target->housing_id;
    }

    public function update(User $actor, User $target): bool
    {
        return $this->view($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->view($actor, $target);
    }

    public function resetPassword(User $actor, User $target): bool
    {
        return $this->view($actor, $target);
    }
}
