<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>

<body>
    <header class="site-header">
        <div class="container header-inner">
            <div class="header-logo">
                <span class="logo-text">attendance-management</span>
            </div>

            <nav class="header-nav">
                @auth
                @if(!Route::is('admin.login'))
                <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="header-nav-menu" aria-label="メニューを開く">
                    <span class="nav-toggle-icon"></span>
                </button>
                <ul id="header-nav-menu">
                    @if(auth()->user()->role === 'admin')
                    <li>
                        <a href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                    </li>
                    <li>
                        <a href="{{ route('stamp_correction_request.list') }}">申請一覧</a>
                    </li>
                    @else
                    <li>
                        <a href="{{ route('attendance.index') }}">勤怠</a>
                    </li>
                    <li>
                        <a href="{{ route('attendance.list') }}">勤怠一覧</a>
                    </li>
                    <li>
                        <a href="{{ route('stamp_correction_request.list') }}">申請</a>
                    </li>
                    @endif
                    <li>
                        <form action="{{ route('logout') }}" method="post" class="logout-form">
                            @csrf
                            @if(auth()->user()->role === 'admin')
                            <input type="hidden" name="login_type" value="admin">
                            @endif
                            <button type="submit" class="logout__button">ログアウト</button>
                        </form>
                    </li>
                </ul>
                @endif
                @endauth
            </nav>
        </div>
    </header>
    <main>
        @yield('content')
    </main>

    <script>
        (function () {
            var toggle = document.querySelector('.nav-toggle');
            var menu = document.getElementById('header-nav-menu');

            if (!toggle || !menu) {
                return;
            }

            function closeMenu() {
                menu.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }

            function openMenu() {
                menu.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }

            toggle.addEventListener('click', function (event) {
                event.stopPropagation();
                if (menu.classList.contains('is-open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            document.addEventListener('click', function (event) {
                if (!menu.classList.contains('is-open')) {
                    return;
                }
                if (!menu.contains(event.target) && event.target !== toggle) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });
        })();
    </script>

    @stack('scripts')
</body>

</html>