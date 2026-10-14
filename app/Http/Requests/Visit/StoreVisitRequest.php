<?php

namespace App\Http\Requests\Visit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customer', 'id_customer'),
                Rule::unique('kunjungan', 'id_customer')->where(
                    fn ($query) => $query->where('tgl_kunjungan', (string) $this->input('visited_at'))
                ),
            ],
            'visited_at' => ['required', 'date', 'before_or_equal:today'],
            'visit_type' => ['required', 'in:dine_in'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Pilih customer dari daftar.',
            'customer_id.exists' => 'Customer tidak ditemukan.',
            'customer_id.unique' => 'Customer sudah memiliki kunjungan pada tanggal tersebut.',
            'visited_at.required' => 'Tanggal kunjungan wajib diisi.',
            'visited_at.before_or_equal' => 'Tanggal kunjungan tidak boleh melewati hari ini.',
            'visit_type.in' => 'Hanya kunjungan dine-in yang dapat dicatat.',
        ];
    }
}
