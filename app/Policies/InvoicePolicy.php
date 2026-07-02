<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return ($user->isResident() && $invoice->user_id === $user->id)
            || ($user->isManager() && $invoice->housing_id === $user->housing_id);
    }

    public function submitPayment(User $user, Invoice $invoice): bool
    {
        return $user->isResident() && $invoice->user_id === $user->id;
    }

    public function verify(User $user, Invoice $invoice): bool
    {
        return $user->isManager() && $invoice->housing_id === $user->housing_id;
    }

    public function reject(User $user, Invoice $invoice): bool
    {
        return $this->verify($user, $invoice);
    }
}
