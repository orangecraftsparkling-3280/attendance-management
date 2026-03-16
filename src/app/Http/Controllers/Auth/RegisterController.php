<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User; // ユーザーモデルを使う
use Illuminate\Support\Facades\Hash; // パスワード暗号化のため
use Illuminate\Support\Facades\Auth; // ログインさせるため
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    // 1. 登録画面を表示する (GET)
    public function show()
    {
        return view('auth.register');
    }

    // 2. 登録処理を実行する (POST)
    public function store(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 1. 認証メールを送信するイベントを発火
        event(new Registered($user));

        // 2. ログイン状態にする
        Auth::login($user);

        // 3. 「メール確認のお願い」画面へリダイレクト（仕様どおり）
        return redirect()->route('verification.notice');
    }
}
