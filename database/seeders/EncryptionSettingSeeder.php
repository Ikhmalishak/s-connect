<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EncryptionSettingSeeder extends Seeder
{
    public function run()
    {
        DB::table('encryption_settings')->insert([
            'table_name'   => 'visitors',
            'field_name'   => 'ic_number',
            'label'        => 'IC Number',
            'is_encrypted' => true,
        ]);

        DB::table('encryption_settings')->insert([
            'table_name'   => 'visitors',
            'field_name'   => 'passport',
            'label'        => 'Passport Number',
            'is_encrypted' => true,
        ]);

        DB::table('encryption_settings')->insert([
            'table_name'   => 'visitors',
            'field_name'   => 'phone_number',
            'label'        => 'Phone Number',
            'is_encrypted' => false,
        ]);

        DB::table('encryption_settings')->insert([
            'table_name'   => 'visitors',
            'field_name'   => 'visitor_name',
            'label'        => 'Visitor Name',
            'is_encrypted' => false,
        ]);
    }
}
