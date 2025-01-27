<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DummyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userData = [
            [
                'name' => 'Admin Satu',
                'username' => 'adminsatu',
                'email' => 'admin1@email.com',
                'password' => bcrypt('adminsatu'),
                'role' => 'admin',
            ],
            [
                'name' => 'Pengurus Satu',
                'username' => 'pengurussatu',
                'email' => 'pengurus1@email.com',
                'password' => bcrypt('pengurussatu'),
                'role' => 'pengurus',
                'housing_address' => 'Jl. Pengurus Satu',
                'housing_name' => 'Pengurus Satu',
                'phone_number' => '081234567890',
            ],
            [
                'name' => 'Warga Satu',
                'username' => 'wargasatu',
                'email' => 'warga1@email.com',
                'password' => bcrypt('wargasatu'),
                'role' => 'warga',
                'house_address' => 'Jl. Warga Satu',
            ],
        ];

        foreach ($userData as $key => $val) {
            User::create($val);
        }
    }
}
