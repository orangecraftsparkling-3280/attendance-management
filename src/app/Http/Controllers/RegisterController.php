<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Models\User; // ユーザーモデルを使う
use Illuminate\Support\Facades\Hash; // パスワード暗号化のため
use Illuminate\Support\Facades\Auth; // ログインさせるため

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
        // データベースにユーザーを作成
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // パスワードは必ず暗号化！
        ]);

        // 登録した直後にそのままログインさせる
        Auth::login($user);

        // 勤怠メイン画面へ移動
        return redirect('/attendance');
    }
}
