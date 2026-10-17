<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class SearchCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:50'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'sort' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function filters(): array
    {
        return [
            'q' => $this->input('q'),
            'bulan' => $this->input('bulan'),
        ];
    }
}
