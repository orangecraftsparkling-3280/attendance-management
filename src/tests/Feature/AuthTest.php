<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_validation()
    {
        $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['name' => 'お名前を入力してください']);

        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ])->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);

        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ])->assertSessionHasErrors(['password' => 'パスワードと一致しません']);

        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_login_validation()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->post('/login', ['email' => '', 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        $this->post('/login', ['email' => 'user@example.com', 'password' => ''])
            ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);

        $this->post('/login', ['email' => 'wrong@example.com', 'password' => 'password123'])
            ->assertSessionHasErrors(['login_error' => 'ログイン情報が登録されていません']);
    }
}
