<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $testUsers = [
            ['name' => 'user1', 'email' => 'user1@example.com'],
            ['name' => 'user2', 'email' => 'user2@example.com'],
            ['name' => 'user3', 'email' => 'user3@example.com'],
        ];
        foreach ($testUsers as $userData) {
            User::factory()
                ->has(
                    Attendance::factory()
                        ->count(15)
                        ->hasRests(fake()->numberBetween(1, 2))
                )
                ->create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'role' => 'user',
                    'password' => Hash::make('password'),
                ]);
        }

        User::factory(7)
            ->has(
                Attendance::factory()
                    ->count(15)
                    ->hasRests(fake()->numberBetween(1, 2))
            )
            ->create([
                'role' => 'user',
            ]);
    }

}
