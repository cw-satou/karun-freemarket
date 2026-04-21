<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'item_name' => ['required'],
            'description' => ['required', 'max:255'],
            'image_path' => ['required', 'mimes:jpeg,png'],
            'brand' => ['nullable'],
            'categories' => ['required'],
            'condition_id' => ['required'],
            'price' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages()
    {
        return [
            'item_name.required' => '商品名を入力してください',
            'description.required' => '商品の説明を入力してください',
            'description.max' => '255文字以内で入力してください',
            'image_path.required' => '商品画像を選択してください',
            'image_path.mimes' => '商品画像の形式は jpeg , png にしてください',
            'categories.required' => 'カテゴリーを選択してください',
            'condition_id.required' => '商品の状態を選択してください',
            'price.required' => '販売価格を入力してください',
            'price.integer' => '販売価格は整数で入力してください',
            'price.min' => '販売価格は0円以上で入力してください',
        ];
    }
    protected function prepareForValidation()
    {
        if ($this->price) {
            $price = mb_convert_kana($this->price, 'n');
            $price = str_replace(',', '', $price);
            $this->merge([
                'price' => $price,
            ]);
        }
    }
}

