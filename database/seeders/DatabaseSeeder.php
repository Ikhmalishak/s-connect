<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => '12345678',
            'role' => 'admin',
            'site' => 'Site 2'
        ]);

        User::create([
            'name' => 'Guard',
            'email' => 'guard@example.com',
            'password' => '12345678',
            'role' => 'guard',
            'site' => 'Site 2'

        ]);

        $this->call([
            DepartmentSeeder::class,
            VisitorCompanySeeder::class
        ]);
    }
}
