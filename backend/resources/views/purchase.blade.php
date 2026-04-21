@extends('layouts.app')
@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection
@section('content')
<div class="purchase-container">
    <div class="purchase__main">
        <div class="product-info">
            <div class="purchase-img-wrapper">
                <img src="{{ asset('storage/' . $product->image_path) }}"
                alt="{{ $product->item_name }}"
                class="purchase-img">
            </div>
            <div class="product-text">
                <p class="product-name">{{ $product->item_name }}</p>
                <p class="product-price">
                    <span class="product-price__yen">¥</span>{{ number_format($product->price) }}
                </p>
            </div>
        </div>
        <hr class="purchase-separator">
        <h3 class="section-title payment">支払い方法</h3>
        <div class="payment-form">
            <select class="payment-method">
                <option value="" {{ $paymentMethod === '' ? 'selected' : '' }} class="payment-option">選択してください</option>
                <option value="convenience" {{ $paymentMethod === 'convenience' ? 'selected' : '' }} class="payment-option">コンビニ払い</option>
                <option value="card" {{ $paymentMethod === 'card' ? 'selected' : '' }} class="payment-option">カード払い</option>
            </select>
        </div>
        <hr class="purchase-separator">
        <div class="address-header">
            <h3 class="section-title">配送先</h3>
            <form action="{{ route('purchase.address.edit', $product->id) }}" method="get" class="address-edit-form">
                <input type="hidden" name="payment_method" class="hidden-address" value="{{ $paymentMethod }}">
                <button type="submit" class="address-edit-btn">変更する</button>
            </form>
        </div>
        <div class="address-form">
        <p class="sending-postcode">
            〒{{ old('sending_postcode', session('sending_postcode', $profile->postal_code ?? '')) ?: '未登録' }}
        </p>
        <p class="sending-address">
            {{ old('sending_address', session('sending_address', $profile->address ?? '')) ?: '未登録' }}
            {{ old('sending_building', session('sending_building', $profile->building ?? '')) }}
        </p>
        </div>
        <hr class="purchase-separator">
    </div>
    <div class="purchase__summary">
        <form action="{{ route('purchase.store', $product->id) }}" method="post" class="summary-form">
            @csrf
            <input type="hidden" name="payment_method" class="hidden-submit" value="{{ $paymentMethod }}">
            <input type="hidden" name="sending_postcode" value="{{ session('sending_postcode', $profile->postal_code ?? '') }}">
            <input type="hidden" name="sending_address" value="{{ session('sending_address', $profile->address ?? '') }}">
            <input type="hidden" name="sending_building" value="{{ session('sending_building', $profile->building ?? '') }}">
            <table class="summary">
                <tr>
                    <th class="summary__label">商品代金</th>
                    <td class="summary__value">¥{{ number_format($product->price) }}</td>
                </tr>
                <tr>
                    <th class="summary__label">支払い方法</th>
                    <td class="summary__value method__summary">
                        {{ $paymentMethod === 'convenience' ? 'コンビニ払い'
                            : ($paymentMethod === 'card' ? 'カード払い'
                            : '選択してください') }}
                    </td>
                </tr>
            </table>
            @error('payment_method')
                <p class="error-message">{{ $message }}</p>
            @enderror
            @error('sending_postcode')
                <p class="error-message">{{ $message }}</p>
            @enderror
            @error('sending_address')
                <p class="error-message">{{ $message }}</p>
            @enderror

            @if(auth()->check() && auth()->id() !== $product->user_id)
                <button type="submit" class="purchase-btn">購入する</button>
            @else
                <p class="error-message">この商品は出品者本人のため購入できません</p>
            @endif
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const paymentSelect = document.querySelector('.payment-method');
        const hiddenAddress = document.querySelector('.hidden-address');
        const hiddenSubmit = document.querySelector('.hidden-submit');
        const summary = document.querySelector('.method__summary');


        paymentSelect.addEventListener('change', function () {
        const val = paymentSelect.value;

        hiddenAddress.value = val;
        hiddenSubmit.value = val;

        summary.textContent = val === 'convenience' ? 'コンビニ払い'
            : val === 'card' ? 'カード払い'
            : '選択してください';
    });
});
</script>
@endsection
