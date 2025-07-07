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
            'name' => 'Ikhmal',
            'email' => 'Ikhmal1410@gmail.com',
            'password' => '12345678'
        ]);

        $this->call([
            DepartmentSeeder::class,
            VisitorCompanySeeder::class
        ]);
    }
}
