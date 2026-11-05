<?php

namespace App\Policies;

use App\Models\PaymentBatch;
use App\Models\User;

class PaymentBatchPolicy
{
    public function view(User $user, PaymentBatch $paymentBatch): bool
    {
        return $user->housing_id === $paymentBatch->housing_id &&
            ($user->isManager() || ($user->isResident() && $paymentBatch->user_id === $user->id));
    }

    public function verify(User $user, PaymentBatch $paymentBatch): bool
    {
        return $user->isManager() && $this->view($user, $paymentBatch);
    }
}
