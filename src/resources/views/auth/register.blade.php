@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('content')
<div class="register-form_content">
    <div class="register-form_heading">
        <h1>会員登録</h1>
    </div>
    <form class="form" action="{{route('register')}}" method="post" novalidate>
        @csrf
        <div class="form_group">
            <div class="form_group-title">
                <label class="form_label-item" for="register-name">名前</label>
            </div>
            <div class="form_group-content">
                <div class="form_input-text">
                    <input type="text" name="name" id="register-name" value="{{ old('name') }}" />
                </div>
                <div class="form_error">
                    @error('name')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form_group">
            <div class="form_group-title">
                <label class="form_label-item" for="register-email">メールアドレス</label>
            </div>
            <div class="form_group-content">
                <div class="form_input-text">
                    <input type="email" name="email" id="register-email" value="{{ old('email') }}" />
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
                <label class="form_label-item" for="register-password">パスワード</label>
            </div>
            <div class="form_group-content">
                <div class="form_input-text">
                    <input type="password" name="password" id="register-password" />
                </div>
                <div class="form_error">
                    @error('password')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form_group">
            <div class="form_group-title">
                <label class="form_label-item" for="register-password-confirmation">パスワード確認</label>
            </div>
            <div class="form_group-content">
                <div class="form_input-text">
                    <input type="password" name="password_confirmation" id="register-password-confirmation" />
                </div>
            </div>
        </div>
        <div class="form_button">
            <button class="form_button-submit" type="submit">登録する</button>
        </div>
    </form>
    <div class="login_link">
        <a class="login_button-submit" href="{{route('login')}}">ログインはこちら</a>
    </div>
</div>
@endsection