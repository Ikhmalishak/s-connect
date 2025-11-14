<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // ===== MODULES =====
        $modules = [
            'visitor' => ['access', 'report'],
            'room-reservation' => ['access', 'report'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "$module.$action"
                ]);
            }
        }

        Permission::create(['name' => 'public']);
        Permission::create(['name' => 'superadmin']);

        // ===== ROLES =====
        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $receptionist = Role::firstOrCreate(['name' => 'receptionist']);
        $guard = Role::firstOrCreate(['name' => 'guard']);

        // ===== ASSIGN PERMISSIONS =====

        // Admin gets *everything*
        $superadmin->givePermissionTo(Permission::all());

        //admin
        $admin->givePermissionTo([
            'visitor.access',
            'public',
        ]);

        // Receptionist can manage visitors fully
        $receptionist->givePermissionTo([
            'visitor.access',
            'public',
            'room-reservation.access',
        ]);

        // Guard can only scan/view visitors
        $guard->givePermissionTo([
            'visitor.access',
            'public'
        ]);
    }
}
