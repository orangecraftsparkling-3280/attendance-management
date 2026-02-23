<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    // ログイン画面を表示する (GET)
    public function show()
    {
        return view('auth.login');
    }

    // ログイン処理を実行する (POST)
    public function authenticate(LoginRequest $request)
    {
        // フォームから送られてきた email と password を取得
        $credentials = $request->only('email', 'password');

        // データベースの照合とログイン試行
        if (Auth::attempt($credentials)) {
            // 成功：セッションを新しくして勤怠画面へ
            $request->session()->regenerate();
            return redirect()->intended('/attendance');
        }

        // 失敗：元の画面（ログイン画面）に戻る
        return back()->with('login_error', 'メールアドレスかパスワードが間違っています。');
    }

    public function logout(Request $request)
    {
        Auth::logout(); // ログアウト実行

        $request->session()->invalidate(); // セッションを無効化
        $request->session()->regenerateToken(); // CSRFトークンを再生成（セキュリティ対策）

        return redirect('/login'); // ログイン画面へ戻す
    }
}
