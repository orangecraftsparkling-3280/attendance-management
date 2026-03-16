<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        // 1. セッション名の上書きや独自のsetcookieはすべて削除します

        // 2. ログインしていて、かつroleがadminであるかチェック
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // 3. 管理者でなければ、管理者のログイン画面へ強制送還
        return redirect()->route('admin.login')->with('error', '管理者権限が必要です。');
    }
}
