@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css')}}">
@endsection

@section('content')
<div class="login-form">
    <h1 class="login-form__heading">ログイン</h1>
    <div class="login-form__inner">
        <form class="login-form__form" action="/login" method="post" novalidate>
            @csrf
            <div class="login-form__group">
                <label class="login-form__label" for="email">メールアドレス</label>
                <input class="login-form__input" type="email" name="email" id="email" value="{{ old('email') }}">
                <p class="login-form__error-message">
                    @error('email')
                    {{ $message }}
                    @enderror
                </p>
            </div>
            <div class="login-form__group">
                <label class="login-form__label" for="password">パスワード</label>
                <input class="login-form__input" type="password" name="password" id="password">
                <p class="login-form__error-message">
                    @error('password')
                    {{ $message }}
                    @enderror
                </p>
            </div>
            <input class="login-form__btn btn" type="submit" value="ログインする">
        </form>
    </div>
</div>
@endsection

@section('link')
<div class="footer__container">
    <a class="footer__link" href="/register">会員登録はこちら</a>
</div>
@endsection