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
        // 1. 管理者を1件作成
        \App\Models\User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $testUsers = [
            ['name' => '太郎', 'email' => 'user1@example.com'],
            ['name' => '次郎', 'email' => 'user2@example.com'],
            ['name' => '三郎', 'email' => 'user3@example.com'],
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
                    'password' => Hash::make('password'), // 全員「password」でログイン可能
                ]);
        }

        // 3. それ以外のランダムな一般ユーザーを7名作成（合計10名にする場合）
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
