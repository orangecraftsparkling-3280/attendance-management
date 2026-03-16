@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('content')
<div class="login-form_content">
    <div class="login-form_heading">
        <h1>ログイン</h1>
    </div>

    <form class="form" action="{{ route('login.post') }}" method="post" novalidate>
        @csrf
        <div class="form_group">
            <div class="form_group-title">
                <span class="form_label-item">メールアドレス</span>
            </div>
            <div class="form_group-content">
                <div class="form_input-text">
                    <input type="email" name="email" value="{{ old('email') }}" />
                </div>
                <div class="form_error">
                    @error('email')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form_group">
            <div class="form_group-title">
                <span class="form_label-item">パスワード</span>
            </div>
            <div class="form_group-content">
                <div class="form_input-text">
                    <input type="password" name="password" />
                </div>
                <div class="form_error">
                    @error('password')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form_error">
            @error('login_error')
            {{ $message }}
            @enderror
        </div>
        <div class="form_button">
            <button class="form_button-submit" type="submit">ログインする</button>
        </div>
    </form>
    <div class="register_link">
        <a class="register_button-submit" href="/register">会員登録はこちら</a>
    </div>
</div>
@endsection