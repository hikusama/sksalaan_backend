<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use App\Models\User; // adjust if your user model is in a different namespace
use Illuminate\Support\Facades\Hash;

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
    }
}
