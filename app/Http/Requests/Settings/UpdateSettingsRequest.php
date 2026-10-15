<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_visits' => ['required', 'integer', 'between:1,100'],
            'window_days' => ['required', 'integer', 'between:1,365'],
            'annual_quota' => ['required', 'integer', 'between:0,65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'min_visits.required' => 'Minimal kunjungan wajib diisi.',
            'min_visits.integer' => 'Minimal kunjungan harus berupa angka.',
            'min_visits.between' => 'Minimal kunjungan harus antara 1 dan 100.',
            'window_days.required' => 'Rentang hari wajib diisi.',
            'window_days.integer' => 'Rentang hari harus berupa angka.',
            'window_days.between' => 'Rentang hari harus antara 1 dan 365.',
            'annual_quota.required' => 'Kuota hampers wajib diisi.',
            'annual_quota.integer' => 'Kuota hampers harus berupa angka.',
            'annual_quota.between' => 'Kuota hampers harus antara 0 dan 65535.',
        ];
    }
}
