@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
<div class="mypage">
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    <div class="mypage__profile">
        <div class="mypage__profile-image">
            @if ($profile && $profile->image_path && \Illuminate\Support\Facades\Storage::exists('public/' . $profile->image_path))
                <img src="{{ asset('storage/' . $profile->image_path) }}" alt="プロフィール画像" class="mypage__profile-image-img">
            @else
                <div class="mypage__profile-image--default"></div>
            @endif
        </div>
        <div class="mypage__profile-info">
            <p class="mypage__username">{{ $user->name }}</p>
            @if($averageRating)
                <div class="rating-stars">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="star {{ $i <= $averageRating ? 'filled' : '' }}">★</span>
                    @endfor
                </div>
            @endif
        </div>
        <div class="mypage__profile-actions">
            <form action="{{ route('profile.edit') }}" method="get">
                <button type="submit" class="mypage__edit-btn">プロフィール編集</button>
            </form>
        </div>
    </div>
    <ul class="mypage__tabs">
        <li>
            <a href="{{ route('mypage.index', ['page' => 'sell']) }}" class="mypage__tab {{ $viewType === 'sell' ? 'active' : '' }}">
                出品した商品
            </a>
        </li>
        <li>
            <a href="{{ route('mypage.index', ['page' => 'buy']) }}" class="mypage__tab {{ $viewType === 'buy' ? 'active' : '' }}">
                購入した商品
            </a>
        </li>
        <li>
            <a href="{{ route('mypage.index', ['page' => 'trading']) }}" class="mypage__tab {{ $viewType === 'trading' ? 'active' : '' }}">
            取引中の商品
            @if($totalUnreadCount > 0)
                <span class="unread-badge">{{ $totalUnreadCount }}</span>
            @endif
            </a>
        </li>
    </ul>
    <div class="mypage__tabs-border"></div>
    @if ($viewType === 'buy')
        <div class="product-list">
            @forelse ($orders as $order)
                @php $product = $order->product; @endphp
                <div class="product-card">
                    <div class="product-card__image-wrapper">
                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->item_name }}" class="product-card__image">
                    </div>
                    <div class="product-card__name">{{ $product->item_name }}</div>
                </div>
            @empty
                <p class="no-products">商品がありません。</p>
            @endforelse
        </div>
    @elseif ($viewType === 'sell')
        <div class="mypage__product-list">
            @forelse ($products as $product)
                <div class="product-card">
                    <div class="product-card__image-wrapper">
                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->item_name }}" class="product-card__image">
                    </div>
                    <div class="product-card__name">{{ $product->item_name }}</div>
                </div>
            @empty
                <p class="no-products">商品がありません。</p>
            @endforelse
        </div>

    @elseif ($viewType === 'trading')
        <div class="mypage__product-list">
            @forelse ($transactions as $transaction)
                @php
                    $product = $transaction->order->product;
                    $unread = $unreadCounts[$transaction->id] ?? 0;
                @endphp
                <a href="{{ route('transaction.show', ['transaction_id' => $transaction->id]) }}" class="mypage__product-card-link">
                    <div class="product-card">
                        <div class="product-card__image-wrapper">
                            @if($unread > 0)
                                <span class="unread-badge-card">{{ $unread }}</span>
                            @endif
                            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->item_name }}" class="product-card__image">
                        </div>
                        <div class="product-card__name">{{ $product->item_name }}</div>
                    </div>
                </a>
            @empty
                <p class="no-products">取引中の商品はありません。</p>
            @endforelse
        </div>
    @endif
</div>
@endsection