@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/address_edit.css') }}">
@endsection

@section('content')

<div class="address__edit-form">
    <h1 class="address-form__heading">住所の変更</h1>
    <div class="address-form__inner">
        <form class="address-form" action="{{ route('purchase.address.update', ['item' => $item->id]) }}" method="post">
            @csrf

            <div class="address__group">
                <label for="sending_postcode">郵便番号</label>
                <input type="text" id="sending_postcode" name="sending_postcode" value="{{ old('sending_postcode') }}">
                @error('sending_postcode')
                    <p class="address-error__message">{{ $message }}</p>
                @enderror
            </div>
            <div class="address__group">
                <label for="sending_address">住所</label>
                <input type="text" id="sending_address" name="sending_address" value="{{ old('sending_address') }}">
                @error('sending_address')
                    <p class="address-error__message">{{ $message }}</p>
                @enderror
            </div>
            <div class="address__group">
                <label for="sending_building">建物名</label>
                <input type="text" id="sending_building" name="sending_building" value="{{ old('sending_building') }}">
            </div>
            <input class="address-form__btn" type="submit" value="更新する">
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const postalInput = document.getElementById('sending_postcode');

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
    });
</script>
@endsection
