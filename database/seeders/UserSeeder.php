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
            'name' => 'Peter Marticio',
            'email' => 'petermarticio@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Superadmin');
        User::create([
            'name' => 'Juanito Mozo',
            'email' => 'juanito.mozo.iv@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Superadmin');
        User::create([
            'name' => 'Classhub Test Admin',
            'email' => 'classhubrep@purelymail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Superadmin');
        User::create([
            'name' => 'Vicky Mozo',
            'email' => 'juanito.cet@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Class Representative');
        User::create([
            'name' => 'Elijah Marticio',
            'email' => 'seafire266@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Class Representative');
        User::create([
            'name' => 'Luis Valdez',
            'email' => 'valdezjose0919@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Class Representative');
        User::create([
            'name' => 'Dona Roldan',
            'email' => 'donaroldan6@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->assignRole('Class Representative');
    }
}
