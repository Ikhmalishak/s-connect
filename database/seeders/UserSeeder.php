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
        $superuser = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@skpres.com',
            'password' => Hash::make('12345678'),
            'site_id' => '2',
            'password_changed_at' => now(),
            'is_first_time_login' => false,
        ]);
        $superuser->assignRole('superadmin');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@skpres.com',
            'password' => Hash::make('12345678'),
            'site_id' => '2',
            'password_changed_at' => now(),
            'is_first_time_login' => false,
        ]);
        $admin->assignRole('admin');

        $guard = User::create([
            'name' => 'Guard',
            'email' => 'guard@skpres.com',
            'password' => Hash::make('12345678'),
            'site_id' => '2',
            'password_changed_at' => now(),
            'is_first_time_login' => false,
        ]);
        $guard->assignRole('guard');

        $receptionist = User::create([
            'name' => 'Receptionist',
            'email' => 'receptionist@skpres.com',
            'password' => Hash::make('12345678'),
            'site_id' => '2',
            'password_changed_at' => now(),
            'is_first_time_login' => false,
        ]);
        $receptionist->assignRole('receptionist');
    }
}
