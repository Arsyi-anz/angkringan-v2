<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrochureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gambar = ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'];

        return [
            'brochure_main' => array_merge(
                ['nullable', 'requiredWithout:brochure_voucher'],
                $gambar
            ),
            'brochure_voucher' => array_merge(
                ['nullable', 'requiredWithout:brochure_main'],
                $gambar
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'brochure_main.requiredWithout' => 'Pilih setidaknya satu gambar brosur untuk disimpan.',
            'brochure_voucher.requiredWithout' => 'Pilih setidaknya satu gambar brosur untuk disimpan.',
            'brochure_main.image' => 'Poster promo harus berupa gambar.',
            'brochure_main.mimes' => 'Poster promo harus berformat JPG, PNG, atau WebP.',
            'brochure_main.max' => 'Ukuran poster promo maksimal 4 MB.',
            'brochure_voucher.image' => 'Lembar voucher harus berupa gambar.',
            'brochure_voucher.mimes' => 'Lembar voucher harus berformat JPG, PNG, atau WebP.',
            'brochure_voucher.max' => 'Ukuran lembar voucher maksimal 4 MB.',
        ];
    }
}
