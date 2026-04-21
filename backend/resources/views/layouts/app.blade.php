<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>coachtechフリマ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__container">
            <div class="header__logo">
                @auth
                <a href="{{ url('/') }}" class="header__logo-link">
                    <img src="{{ asset('images/logo.svg') }}" alt="coachtech" class="logo">
                </a>
                @else
                <a href="{{ url('/?tab=recommend') }}" class="header__logo-link">
            <img src="{{ asset('images/logo.svg') }}" alt="coachtech" class="logo">
        </a>
                @endauth
            </div>
            @if (
                !request()->is('login') &&
                !request()->is('register') &&
                !request()->is('verify-check') &&
                !request()->is('verification*') &&
                !request()->is('transaction*')
                )
            <div class="header__search">
                <form action="{{ url('/') }}" method="GET">
                    <input type="text" name="keyword"  value="{{ request('keyword') }}" placeholder="なにをお探しですか？" />
                    @if(request()->has('tab'))
                        <input type="hidden" name="tab" value="{{ request('tab') }}">
                    @endif
                </form>
            </div>
            <nav class="header__nav">
                <ul>
                    @auth
                        <li>
                            <a href="{{ route('logout') }}" class="header__nav-link"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                ログアウト
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </li>
                    @else
                        <li><a href="{{ route('login') }}" class="header__nav-link">ログイン</a></li>
                    @endauth
                    <li><a href="{{ route('mypage.index') }}" class="header__nav-link">マイページ</a></li>
                    <li><a href="{{ route('sell.create') }}" class="nav__btn">出品</a></li>
                </ul>
            </nav>
            @endif
        </div>
    </header>
    <main class="content">
        @yield('content')
    </main>
    <footer class="footer">
    @yield('link')
    </footer>
    @yield('js')
</body>
</html>