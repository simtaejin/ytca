<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => '관리자',
            'email' => 'admin@example.com',
            'password' => Hash::make('password1234'),
        ]);

        User::create([
            'name' => 'TJSIM',
            'email' => 'tjsim00@gmail.com',
            'password' => Hash::make('password1234'),
        ]);
    }
}
