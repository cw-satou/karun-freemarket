@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/exhibition.css') }}" />
@endsection

@section('content')
<div class="exhibition-container">
    <h1 class="exhibition-title">商品の出品</h1>
    <form action="{{ route('sell.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-section">
            <label class="form-label">商品画像</label>
            <div class="image-upload-box" id="imagePreview">
                <input type="file" name="image_path" id="image_path" hidden>
                <label for="image_path" class="image-upload-btn">画像を選択する</label>
            <div id="previewImage"></div>
            </div>
            @error('image_path')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <h2 class="section-title">商品の詳細</h2>
        <hr class="section-divider">
        <div class="form-section">
            <label class="form-label">カテゴリー</label>
            <div class="category-list">
                @foreach($categories as $category)
                    <label class="category-pill" for="category-{{ $category->id }}">
                        <input type="checkbox" id="category-{{ $category->id }}" name="categories[]" value="{{ $category->id }}"{{ (is_array(old('categories')) && in_array($category->id, old('categories'))) ? 'checked' : '' }}>
                        <span>{{ $category->category }}</span>
                    </label>
                @endforeach
            </div>
            @error('categories')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-section">
            <label class="form-label" for="condition_id">商品の状態</label>
            <div class="condition-select-wrapper">
                <select name="condition_id" id="condition_id">
                    <option value="">選択してください</option>
                    @foreach($conditions as $condition)
                        <option value="{{ $condition->id }}"{{ old('condition_id') == $condition->id ? 'selected' : '' }}>{{ $condition->name }}</option>
                    @endforeach
                </select>
            </div>
            @error('condition_id')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <h2 class="section-title">商品名と説明</h2>
        <hr class="section-divider">
        <div class="form-section">
            <label class="form-label" for="item_name">商品名</label>
            <input type="text" id="item_name" name="item_name" class="form-input" value="{{ old('item_name') }}">
            @error('item_name')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-section">
            <label class="form-label" for="brand">ブランド名</label>
            <input type="text" id="brand" name="brand" class="form-input" value="{{ old('brand') }}">
        </div>
        <div class="form-section">
            <label class="form-label" for="description">商品の説明</label>
            <textarea name="description" id="description" class="form-textarea">{{ old('description') }}</textarea>
            @error('description')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-section">
            <label class="form-label" for="price">販売価格</label>
            <div class="price-wrapper">
                <input type="text" id="price" name="price" class="form-input price-input" value="{{ old('price') }}">
            </div>
            @error('price')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-section">
            <input type="submit" value="出品する" class="submit-btn">
        </div>
    </form>
</div>
@endsection

@section('js')
<script>
document.addEventListener("DOMContentLoaded", () => {
    const textarea = document.getElementById("description");
    textarea.addEventListener("input", function() {
        this.style.height = "auto";
        this.style.height = this.scrollHeight + "px";
    });
    const input = document.getElementById("image_path");
    const previewImage = document.getElementById("previewImage");

    input.addEventListener("change", (event) => {
    const file = event.target.files[0];
        if (!file) return;

        previewImage.innerHTML = "";

    const img = document.createElement("img");
        img.src = URL.createObjectURL(file);
        img.style.maxWidth = "100%";
        img.style.height = "auto";

        previewImage.appendChild(img);
    });
    const priceInput = document.getElementById("price");
    priceInput.addEventListener("input", (e) => {
        let cursorPosition = priceInput.selectionStart;
        let value = priceInput.value.replace(/[０-９Ａ-Ｚａ-ｚ]/g, (s) => {
            return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
        });

        value = value.replace(/,/g, '');
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        priceInput.value = value;

        priceInput.selectionStart = priceInput.selectionEnd = cursorPosition;
    });
});
</script>
@endsection