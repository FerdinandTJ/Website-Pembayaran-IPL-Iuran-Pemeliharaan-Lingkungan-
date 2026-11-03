<?php

namespace App\Services;

use App\Models\User;

class ImportIdentity
{
    public function resident(array $row, User $actor): array
    {
        $usernames = User::whereRaw('LOWER(username) = ?', [mb_strtolower($row['username'])])->limit(2)->get();
        $emails = User::whereRaw('LOWER(email) = ?', [mb_strtolower($row['email'])])->limit(2)->get();
        if ($usernames->count() > 1 || $emails->count() > 1) {
            return [null, ['Identitas akun tidak unik jika huruf besar/kecil diabaikan. Perbaiki data akun terlebih dahulu.']];
        }
        $username = $usernames->first();
        $email = $emails->first();
        if ($username && $email && $username->id !== $email->id) {
            return [null, ['Username dan email menunjuk dua akun berbeda. Perbaiki identitas sebelum mengimpor.']];
        }
        $target = $username ?? $email;
        if ($target && (! $target->isResident() || $target->housing_id !== $actor->housing_id)) {
            return [null, ['Username/email sudah digunakan akun yang tidak dapat diperbarui oleh perumahan ini.']];
        }

        return [$target, []];
    }

    public function fingerprint(?User $user): ?string
    {
        return $user ? hash('sha256', json_encode($user->only(['name', 'username', 'email', 'phone_number', 'house_id', 'house_address', 'is_active']) + ['password' => $user->getRawOriginal('password')], JSON_THROW_ON_ERROR)) : null;
    }
}
