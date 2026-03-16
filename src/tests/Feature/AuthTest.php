<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase; // テストごとにDBをリセットする

    /**
     * 会員登録：バリデーションテスト
     */
    public function test_registration_validation()
    {
        // 1. 名前が未入力の場合
        $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['name' => 'お名前を入力してください']);

        // 2. メールアドレスが未入力の場合
        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        // 3. パスワードが8文字未満の場合
        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ])->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);

        // 4. パスワードが一致しない場合
        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ])->assertSessionHasErrors(['password' => 'パスワードと一致しません']);

        // 5. パスワードが未入力の場合
        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * ログイン：バリデーションテスト
     */
    public function test_login_validation()
    {
        // ユーザーを一人作成しておく
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 1. メールアドレスが未入力の場合
        $this->post('/login', ['email' => '', 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        // 2. パスワードが未入力の場合
        $this->post('/login', ['email' => 'user@example.com', 'password' => ''])
            ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);

        // 3. 登録内容と一致しない場合
        $this->post('/login', ['email' => 'wrong@example.com', 'password' => 'password123'])
            ->assertSessionHasErrors(['login_error' => 'ログイン情報が登録されていません']);
    }
}
