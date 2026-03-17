<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>

<body>
    <header class="site-header">
        <div class="container header-inner">
            <div class="header-logo">
                <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" alt="ロゴ" class="logo-img">
            </div>

            <nav class="header-nav">
                @auth
                @if(!Route::is('admin.login'))

                @if(auth()->user()->role === 'admin')
                <a href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                <a href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                <a href="{{ route('stamp_correction_request.list') }}">申請一覧</a>
                @else
                <a href="{{ route('attendance.index') }}">勤怠</a>
                <a href="{{ route('attendance.list') }}">勤怠一覧</a>
                <a href="{{ route('stamp_correction_request.list') }}">申請</a>
                @endif

                <form action="{{ route('logout') }}" method="post" class="logout-form" >
                    @csrf

                    @if(auth()->user()->role === 'admin')
                    <input type="hidden" name="login_type" value="admin">
                    @endif

                    <button type="submit" class="logout__button">ログアウト</button>
                </form>

                @endif
                @endauth
            </nav>
        </div>
    </header>
    <main>
        @yield('content')
    </main>
    @stack('scripts')
</body>

</html>