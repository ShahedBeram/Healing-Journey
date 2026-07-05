<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'full_name' => 'Admin System',
            'email' => 'admin@system.com',
            'password' => Hash::make('password123'),
            'phone' => '0590000000',
            'user_type' => 'admin',
            'account_status' => 'approved',
        ]);
    }
}
