<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionRevoker
{
    public function revoke(User $user, ?string $except = null): void
    {
        PushSubscription::where('user_id', $user->id)->when($except, fn ($q) => $q->where('session_hash', '!=', hash('sha256', $except)))->delete();
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)->when($except, fn ($q) => $q->where('id', '!=', $except))->delete();
    }
}
