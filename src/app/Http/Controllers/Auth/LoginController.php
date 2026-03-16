<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
            // 管理者が一般画面から入ろうとしたら弾く（オプション）
            if (Auth::user()->role === 'admin') {
                Auth::logout();
                return back()->withErrors(['login_error' => '管理者の方は管理者ログインからログインしてください']);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('attendance.index'));
        }
        // 失敗：元の画面（ログイン画面）に戻る
        return back()->withErrors([
            'login_error' => 'ログイン情報が登録されていません',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        setcookie('is_admin_user', '', time() - 3600, '/');

        // 1. ログアウトする前に、現在のユーザーが管理者かどうかをチェック
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';

        // 2. ログアウト実行
        Auth::logout();

        // 3. セッションとトークンの破棄
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 4. 権限に応じてリダイレクト先を分ける
        if ($isAdmin) {
            return redirect()->route('admin.login'); // 管理者ログインへ
        }

        return redirect()->route('login'); // 一般ユーザーログインへ
    }
}
