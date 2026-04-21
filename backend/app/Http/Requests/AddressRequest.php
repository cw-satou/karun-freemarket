<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
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
            'sending_postcode' => ['required', 'regex:/^\d{3}-\d{4}$/'],
            'sending_address' => ['required'],
            'sending_building' => ['nullable'],
        ];
    }
    public function messages(): array
    {
        return [
            'sending_postcode.required' => '郵便番号を入力してください。',
            'sending_postcode.regex' => '郵便番号はハイフンありの８文字で入力してください',
            'sending_address.required' => '住所を入力してください。',
        ];
    }
    protected function prepareForValidation()
    {
    if ($this->sending_postcode) {
        $cleaned = mb_convert_kana($this->sending_postcode, 'n');
        $cleaned = preg_replace('/[^0-9]/', '', $cleaned);

        if (preg_match('/^\d{7}$/', $cleaned)) {
            $this->merge([
                'sending_postcode' => substr($cleaned, 0, 3) . '-' . substr($cleaned, 3),
            ]);
        }
    }
    }
}
