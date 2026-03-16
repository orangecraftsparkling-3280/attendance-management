<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * メール認証誘導画面を表示
     */
    public function show(Request $request)
    {
        // すでに認証済みの場合は打刻画面へリダイレクト
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('attendance.index');
        }

        $email = $request->user()->email;
        $domain = substr(strrchr($email, "@"), 1);

        // 主要なメールサービスのURLリスト
        $mailServices = [
            'gmail.com'      => 'https://mail.google.com/',
            'yahoo.co.jp'    => 'https://mail.yahoo.co.jp/',
            'icloud.com'     => 'https://www.icloud.com/mail',
            'outlook.jp'     => 'https://outlook.live.com/',
            'outlook.com'    => 'https://outlook.live.com/',
            'hotmail.com'    => 'https://outlook.live.com/',
            'example.com'    => 'http://localhost:8025',
            'test.com'       => 'http://localhost:8025',
        ];

        $targetUrl = $mailServices[$domain] ?? null;

        return view('auth.verify-email', compact('email', 'domain', 'targetUrl'));
    }
}
