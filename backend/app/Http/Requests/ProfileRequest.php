<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
            'name' => ['required', 'max:20'],
            'postal_code' => ['required', 'regex:/^\d{3}-\d{4}$/'],
            'address' => ['required', 'max:255'],
            'profile_image' => ['nullable', 'mimes:jpeg,png'],
            'building' => ['nullable', 'max:255'],

        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => '名前を入力してください',
            'name.max' => '名前は20文字以内で入力してください',
            'postal_code.required' => '郵便番号を入力してください',
            'postal_code.regex' => '郵便番号はハイフンありの８文字で入力してください',
            'address.required' => '住所を入力してください',
            'profile_image.mimes' => '画像形式は jpeg, pngにしてください',
        ];
    }
    protected function prepareForValidation()
    {
    if ($this->postal_code) {
            $cleaned = mb_convert_kana($this->postal_code, 'n');
            $cleaned = preg_replace('/[^0-9]/', '', $cleaned);

            if (preg_match('/^\d{7}$/', $cleaned)) {
                $this->merge([
                    'postal_code' => substr($cleaned, 0, 3) . '-' . substr($cleaned, 3),
                ]);
            }
        }
    }

}
