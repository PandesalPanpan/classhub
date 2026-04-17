<?php

namespace Database\Seeders;

use App\Models\User;
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
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Superadmin');
        User::create([
            'name' => 'Moderator',
            'email' => 'moderator@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Admin');
        User::create([
            'name' => 'Class Representative',
            'email' => 'class@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Class Representative');
        User::create([
            'name' => 'Peter Marticio',
            'email' => 'petermarticio@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Class Representative');
        User::create([
            'name' => 'Luis Valdez',
            'email' => 'luisvaldez@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Class Representative');
        User::create([
            'name' => 'Dona Roldan',
            'email' => 'donaroldan@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Class Representative');
        User::create([
            'name' => 'Juanito Mozo',
            'email' => 'juanitomozo@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Class Representative');
    }
}
