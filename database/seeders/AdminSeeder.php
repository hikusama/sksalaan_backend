<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use App\Models\User; // adjust if your user model is in a different namespace
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $user = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'userName' => 'Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin123')),
                'role' => 'Admin'
            ]
        );
        Admin::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'sksalaan',
                'position' => 'SKChairman',
            ]
        );
        $sql = File::get(database_path('sql/sksalaan_database.sql'));
        DB::unprepared($sql);
    }
}
