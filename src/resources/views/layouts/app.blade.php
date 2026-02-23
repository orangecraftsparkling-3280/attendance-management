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
                <a href="#">勤怠</a>
                <a href="#">勤怠一覧</a>
                <a href="#">申請</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button type="submit" class="logout__button">ログアウト</button>
                </form>
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