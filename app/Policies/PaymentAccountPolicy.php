<?php

namespace App\Policies;

use App\Models\PaymentAccount;
use App\Models\User;

class PaymentAccountPolicy
{
    public function update(User $user, PaymentAccount $paymentAccount): bool
    {
        return $user->isManager() && $user->housing_id === $paymentAccount->housing_id;
    }

    public function delete(User $user, PaymentAccount $paymentAccount): bool
    {
        return $this->update($user, $paymentAccount);
    }
}
