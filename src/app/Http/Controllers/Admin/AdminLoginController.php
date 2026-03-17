<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->role === 'admin') {
                setcookie('is_admin_user', '1', 0, '/');

                $request->session()->regenerate();
                return redirect()->route('admin.attendance.list');
            }

            Auth::logout();
            return back()->withErrors(['login_failed' => '管理者権限がありません。']);
        }

        return back()->withErrors(['login_failed' => 'ログインに失敗しました。']);
    }
}
