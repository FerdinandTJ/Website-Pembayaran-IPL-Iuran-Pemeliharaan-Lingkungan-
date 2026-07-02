<?php

namespace App\Policies;

use App\Models\Cashflow;
use App\Models\User;

class CashflowPolicy
{
    public function update(User $user, Cashflow $cashflow): bool
    {
        return $user->isManager()
            && $cashflow->housing_id === $user->housing_id
            && $cashflow->isManual();
    }

    public function delete(User $user, Cashflow $cashflow): bool
    {
        return $this->update($user, $cashflow);
    }
}
