<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin
        {--name= : Nama lengkap admin}
        {--username= : Username admin}
        {--email= : Email admin}
        {--password= : Password admin}';

    protected $description = 'Membuat satu akun admin permanen EnviroPay';

    public function handle(): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->error('Akun admin sudah ada. EnviroPay hanya mengizinkan satu admin.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->option('name') ?: $this->ask('Nama lengkap'),
            'username' => $this->option('username') ?: $this->ask('Username'),
            'email' => $this->option('email') ?: $this->ask('Email'),
            'password' => $this->option('password') ?: $this->secret('Password (minimal 8 karakter)'),
        ];

        $validated = validator($data, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', 'min:8'],
        ])->validate();

        User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->info('Admin berhasil dibuat.');

        return self::SUCCESS;
    }
}
