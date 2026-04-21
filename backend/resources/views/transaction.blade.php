@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/transaction.css') }}">
@endsection

@section('content')
    @if(session('success'))
        <div class="flash-message">
            {{ session('success') }}
        </div>
    @endif
<div class="transaction-container">
    <div class="transaction-sidebar">
        <h2 class="transaction-sidebar-title">その他の取引</h2>
        <ul class="transaction-sidebar-list">
            @foreach($sidebarTransactions as $sidebarTransaction)
                @php $product = $sidebarTransaction->order?->product; @endphp
                @if($product)
                    <li class="transaction-sidebar-item">
                        <a href="{{ route('transaction.show', ['transaction_id' => $sidebarTransaction->id]) }}" class="sidebar-product-name">
                            {{ $product->item_name }}
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
    <div class="transaction-main">
        <div class="transaction-main-inner">
            <div class="transaction-main-header">
                <div class="transaction-main-user-icon">
                    @if($partner->profile_image_url)
                        <img src="{{ $partner->profile_image_url }}" alt="相手のアイコン">
                    @else
                        <div class="message-user-icon--default"></div>
                    @endif
                </div>
                <h1 class="transaction-main-title">「{{ $partner->name }}」さんとの取引画面</h1>
                @php
                    $alreadyRated = \App\Models\Rating::where('transaction_id', $transaction->id)
                        ->where('rater_id', auth()->id())
                        ->exists();
                @endphp
                @if($isBuyer)
                    @if(!$alreadyRated)
                        <button type="button" class="transaction-complete-btn">取引を完了する</button>
                    @else
                        <button type="button" class="transaction-complete-btn completed" disabled>評価完了</button>
                    @endif
                @endif
            </div>
            <hr class="transaction-divider">
            <div class="transaction-product-info">
                <div class="transaction-product-image">
                    <img src="{{ asset('storage/' . $transaction->order->product->image_path) }}"
                        alt="{{ $transaction->order->product->item_name }}">
                </div>
                <div class="transaction-product-details">
                    <h2 class="transaction-product-name">{{ $transaction->order->product->item_name }}</h2>
                    <p class="transaction-product-price">¥{{ number_format($transaction->order->product->price) }}</p>
                </div>
            </div>
            <hr class="transaction-divider">
            <ul class="transaction-messages">
                @foreach($messages as $message)
                    @php $isSelf = $message->user_id === $user->id; @endphp
                    <li class="message-row {{ $isSelf ? 'message-self' : 'message-other' }}">
                        <div class="message-header {{ $isSelf ? 'message-self-header' : '' }}">
                            @if(!$isSelf)
                                <div class="message-user-icon">
                                    @if($message->profile_image_url)
                                        <img src="{{ $message->profile_image_url }}" alt="プロフィール画像">
                                    @else
                                        <div class="message-user-icon--default"></div>
                                    @endif
                                </div>
                                <span class="message-user-name">{{ $message->user->name }}</span>
                            @else
                                <span class="message-user-name">{{ $message->user->name }}</span>
                                <div class="message-user-icon">
                                    @if($message->profile_image_url)
                                        <img src="{{ $message->profile_image_url }}" alt="プロフィール画像">
                                    @else
                                        <div class="message-user-icon--default"></div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="message-content-wrapper">
                            <div class="message-bubble">
                                <p class="message-text view-mode">
                                    {{ $message->content }}
                                </p>
                                @if($message->edited_at)
                                    <small class="message-edited">(編集済み)</small>
                                @endif
                                @if($isSelf)
                                    <form method="POST"
                                        action="{{ route('message.update', ['message_id' => $message->id]) }}"
                                        id="edit-form-{{ $message->id }}"
                                        class="edit-form"
                                        style="display:none;">
                                        @csrf
                                        @method('PATCH')
                                        <textarea name="message" class="message-text-edit">{{ $message->content }}</textarea>
                                    </form>
                                @endif
                                @if($message->image_path)
                                    <a href="{{ asset('storage/' . $message->image_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $message->image_path) }}" class="message-image">
                                    </a>
                                @endif
                            </div>
                            @if($isSelf)
                                <div class="message-actions">
                                    <button type="button" class="edit-btn">編集</button>
                                    <button type="submit"
                                            form="edit-form-{{ $message->id }}"
                                            class="save-btn"
                                            style="display:none;">保存</button>
                                    <button type="button"
                                            class="cancel-btn"
                                            style="display:none;">キャンセル</button>
                                    <form method="POST" action="{{ route('message.destroy', ['message_id' => $message->id]) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="message-delete-btn">削除</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="transaction-input-form-wrapper">
                <form method="POST" action="{{ route('message.store', ['transaction_id' => $transaction->id]) }}" enctype="multipart/form-data">
                    @csrf
                    @if ($errors->has('message') || $errors->has('image'))
                        <div class="error-wrapper">
                            @error('message')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                            @error('image')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                    <div class="transaction-input-form">
                        <textarea name="message"
                                class="transaction-input"
                                placeholder="取引メッセージを入力してください">{{ old('message') }}</textarea>

                        <label class="transaction-add-image-btn-label">
                            画像を追加
                            <input type="file" name="image" class="transaction-add-image-btn">
                        </label>
                        <button type="submit" class="transaction-send-btn">
                            <img src="{{ asset('images/send.jpg') }}" alt="送信">
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 評価モーダル  -->
    @php
        $ratingsCount = $transaction->ratings()->count();

        $alreadyRated = \App\Models\Rating::where('transaction_id', $transaction->id)
            ->where('rater_id', auth()->id())
            ->exists();

        $isSeller = !$isBuyer;
        $shouldAutoOpen = $isSeller && !$alreadyRated && $ratingsCount >= 1;

        $ratingRoute = $isBuyer
            ? route('transaction.complete', ['transaction_id' => $transaction->id])
            : route('transaction.rating.store', ['transaction_id' => $transaction->id]);
    @endphp
    @if((!$alreadyRated && ($isBuyer || $isSeller)))
    <div id="ratingModal"
        class="rating-modal"
        data-auto-open="{{ $shouldAutoOpen ? 1 : 0 }}"
        style="display:none;">
        <div class="rating-modal-content">
            <h2 class="rating-title">取引が完了しました。</h2>
            <hr class="rating-line">
            <p class="rating-question">今回の取引相手はどうでしたか？</p>

            <div class="rating-stars">
                @for($i=1; $i<=5; $i++)
                    <span class="star" data-value="{{ $i }}">&#9733;</span>
                @endfor
            </div>
            <p class="rating-error"></p>
            <hr class="rating-line">
            <form id="rating-form" method="POST" action="{{ $ratingRoute }}">
                @csrf
                <input type="hidden" name="score" id="score-input">

                <div class="rating-submit-wrapper">
                    <button type="submit" class="rating-submit-btn">
                        送信する
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('ratingModal');
    if (modal) {
        const completeBtn = document.querySelector('.transaction-complete-btn');
        const stars = modal.querySelectorAll('.star');
        const ratingForm = document.getElementById('rating-form');
        const scoreInput = document.getElementById('score-input');
        const autoOpen = modal.dataset.autoOpen === '1';
        const modalContent = modal.querySelector('.rating-modal-content');

        if (autoOpen) modal.style.display = 'flex';
        if (completeBtn) {
            completeBtn.addEventListener('click', () => {
                modal.style.display = 'flex';
            });
        }

        modal.addEventListener('click', function(e) {
            if (!modalContent.contains(e.target)) {
                modal.style.display = 'none';
            }
        });

        stars.forEach((star, index) => {
            star.addEventListener('click', function () {
                stars.forEach(s => s.classList.remove('active'));
                for (let i = 0; i <= index; i++) stars[i].classList.add('active');
                scoreInput.value = index + 1;
            });
        });
        if (ratingForm) {
            ratingForm.addEventListener('submit', (e) => {
                if (!scoreInput.value) {
                    e.preventDefault();
                    const errorMsg = modal.querySelector('.rating-error');
                    if (errorMsg) {
                        errorMsg.textContent = '評価の数を選択してください';
                        errorMsg.style.display = 'block';
                    }
                }
            });
        }
    }

    const resizeTextarea = (el, minHeight = null) => {
        el.style.height = 'auto';
        if (minHeight) el.style.height = minHeight;
        el.style.height = el.scrollHeight + 'px';
    };

    const initInputTextarea = () => {
        const inputArea = document.querySelector('.transaction-input');
        if (!inputArea) return;
        const storageKey = "message_draft_{{ auth()->id() }}_{{ $transaction->id }}";
        const saved = localStorage.getItem(storageKey);
        if (saved) inputArea.value = saved;

        resizeTextarea(inputArea, '44px');

        inputArea.addEventListener('input', function () {
            resizeTextarea(this, '44px');
            localStorage.setItem(storageKey, this.value);
        });

        const form = inputArea.closest('form');
        if (form) {
            form.addEventListener('submit', () => {
                localStorage.removeItem(storageKey);
            });
        }
    };
    initInputTextarea();

    const initEditTextareas = () => {
        const editTextareas = document.querySelectorAll('.message-text-edit');
        editTextareas.forEach(textarea => {
            resizeTextarea(textarea);
            textarea.addEventListener('input', function () {
                resizeTextarea(this);
            });
        });
    };
    initEditTextareas();

    const initEditButtons = () => {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function () {
                const wrapper = this.closest('.message-content-wrapper');
                const view = wrapper.querySelector('.view-mode');
                const form = wrapper.querySelector('.edit-form');
                const textarea = form.querySelector('.message-text-edit');
                const saveBtn = wrapper.querySelector('.save-btn');
                const cancelBtn = wrapper.querySelector('.cancel-btn');

                view.style.display = 'none';
                form.style.display = 'block';

                requestAnimationFrame(() => {
                    resizeTextarea(textarea);
                });

                this.style.display = 'none';
                saveBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
            });
        });

        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function () {
                const wrapper = this.closest('.message-content-wrapper');
                const view = wrapper.querySelector('.view-mode');
                const form = wrapper.querySelector('.edit-form');
                const editBtn = wrapper.querySelector('.edit-btn');
                const saveBtn = wrapper.querySelector('.save-btn');

                form.style.display = 'none';
                view.style.display = 'block';

                editBtn.style.display = 'inline-block';
                saveBtn.style.display = 'none';
                this.style.display = 'none';
            });
        });
    };
    initEditButtons();

    const scrollToBottom = () => {
        window.scrollTo(0, document.body.scrollHeight);
    };
    scrollToBottom();

    const messageForm = document.querySelector('form[action*="message.store"]');
    if (messageForm) {
        messageForm.addEventListener('submit', () => {
            setTimeout(scrollToBottom, 100);
        });
    }
});
</script>
@endsection


