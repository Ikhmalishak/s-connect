<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@skpres.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'site' => 'Site 2',
        ]);

        User::create([
            'name' => 'Guard',
            'email' => 'guard@skpres.com',
            'password' => Hash::make('12345678'),
            'role' => 'guard',
            'site' => 'Site 2',
        ]);

        User::create([
            'name' => 'Receptionist',
            'email' => 'receptionist@skpres.com',
            'password' => Hash::make('12345678'),
            'role' => 'receptionist',
            'site' => 'Site 2',
        ]);
    }
}
