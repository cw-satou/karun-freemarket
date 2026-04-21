<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'payment_method' => ['required'],
            'sending_postcode' => ['required'],
            'sending_address' => ['required'],
            'sending_building'  => ['nullable', 'max:255'],
        ];
    }
    public function messages()
    {
        return [
            'payment_method.required'   => '支払い方法を選択してください',
            'sending_postcode.required' => '郵便番号は必須です',
            'sending_address.required'  => '住所は必須です',
        ];
    }
}
