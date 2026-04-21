@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile_edit.css') }}">
@endsection

@section('content')
<div class="profile">
    <h1 class="profile__title">プロフィール設定</h1>
    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="profile__form">
        @csrf
        <div class="profile__image-section">
            <div class="profile__image-circle">
                @if ($profile && $profile->image_path && \Illuminate\Support\Facades\Storage::exists('public/' . $profile->image_path))
                    <img src="{{ asset('storage/' . $profile->image_path) }}" alt="プロフィール画像" class="profile__image">
                @else
                    <div class="profile__image--default"></div>
                @endif
            </div>
            <label class="profile__upload">
                画像を選択する
                <input type="file" name="profile_image" accept="image/*" class="profile__input-file" hidden>
            </label>
        </div>
        @error('profile_image')
            <p class="profile__error">{{ $message }}</p>
        @enderror
        <div class="profile__group">
            <label for="name" class="profile__label">ユーザー名</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="profile__input-text">
            @error('name')
                <p class="profile__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="profile__group">
            <label for="postal_code" class="profile__label">郵便番号</label>
            <input type="text" name="postal_code" value="{{ old('postal_code', $profile->postal_code) }}" class="profile__input-postal">
            @error('postal_code')
                <p class="profile__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="profile__group">
            <label for="address" class="profile__label">住所</label>
            <input type="text" name="address" value="{{ old('address', $profile->address) }}" class="profile__input-text">
            @error('address')
                <p class="profile__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="profile__group">
            <label for="building" class="profile__label">建物名</label>
            <input type="text" name="building" value="{{ old('building', $profile->building) }}" class="profile__input-text">
        </div>
        <div class="profile__submit-container">
            <input type="submit" value="更新する" class="profile__btn">
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const postalInput = document.querySelector('.profile__input-postal');

        postalInput.addEventListener('input', function (e) {
            let value = e.target.value;

            value = value.replace(/[０-９]/g, function(s) {
                return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
            });

            value = value.replace(/[^\d]/g, '').slice(0, 7);

            if (value.length >= 4) {
                e.target.value = value.slice(0, 3) + '-' + value.slice(3);
            } else {
                e.target.value = value;
            }
        });
        const inputFile = document.querySelector('.profile__input-file');
        const img = document.querySelector('.profile__image');

        inputFile.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (!file) return;

            img.src = URL.createObjectURL(file);
            img.classList.remove('profile__image--hidden');
        });
    });
</script>
@endsection
