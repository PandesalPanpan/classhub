<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ])->assignRole('Superadmin');
        User::create([
            'name' => 'Moderator',
            'email' => 'moderator@test.com',
            'password' => Hash::make('password'),
        ])->assignRole('Admin');
        User::create([
            'name' => 'Class Representative',
            'email' => 'class@test.com',
            'password' => Hash::make('password'),
        ])->assignRole('Class Representative');
    }
}
