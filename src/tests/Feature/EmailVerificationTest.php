<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 会員登録後、認証メールが送信される
     * テスト内容：会員登録をすると、登録したアドレス宛にVerifyEmail通知が飛ぶこと
     */
    public function test_verification_email_is_sent_after_registration()
    {
        // 実際にメールは飛ばさず、通知が飛んだことだけを記録する設定
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // ユーザーがDBに保存されているか確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // Laravel標準のメール認証通知（VerifyEmail）がユーザーに送られたか確認
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * 2. 未認証ユーザーに対する誘導画面の表示
     * テスト内容：メール認証が終わっていないユーザーが、誘導画面にアクセスできること
     */
    public function test_unverified_user_can_see_verification_notice()
    {
        // 認証日時がnull（未認証）のユーザーを作成
        $user = User::factory()->create(['email_verified_at' => null]);

        // そのユーザーとしてログインし、認証誘導ページにアクセス
        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('認証'); // 画面内に「認証」という文字があるか確認
    }

    /**
     * 3. メール認証を完了すると、勤怠登録画面に遷移する
     * テスト内容：メール内のリンク（署名付きURL）をクリックすると、認証済みになりTOPへ戻る事
     */
    public function test_email_can_be_verified_and_redirects_to_attendance()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // システムが発行する「署名付きURL」をシミュレート
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // そのURLにアクセス
        $response = $this->actingAs($user)->get($verificationUrl);

        // 期待挙動：DBの email_verified_at に日付が入っていること
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // 期待挙動：勤怠登録画面（attendance.index）へリダイレクトされること
        $response->assertRedirect(route('attendance.index'));
    }
}
