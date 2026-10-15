<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class CheckVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = preg_replace('/\s+/', '', (string) $this->input('code'));

        $this->merge(['code' => strtoupper($code)]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Masukkan kode voucher.',
            'code.max' => 'Kode voucher maksimal 10 karakter.',
        ];
    }
}
