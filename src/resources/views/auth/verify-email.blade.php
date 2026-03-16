@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-container">
    <div class="verify-card">
        <p class="verify-text">
            登録していただいたメールアドレスに承認メールを送付しました。<br>
            メール承認を完了してください。
        </p>

        @if($targetUrl)
        <a href="{{ $targetUrl }}" target="_blank" rel="noopener noreferrer" class="btn-primary">
            認証はこちらから
        </a>
        @endif

        <div class="resend-section">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-resend">
                    認証メールを再送する
                </button>
            </form>
        </div>
    </div>
</div>
@endsection