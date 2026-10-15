<?php

namespace App\Http\Requests\Voucher;

use App\Support\VoucherKuota;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['source' => strtoupper(trim((string) $this->input('source')))]);
    }

    public function rules(): array
    {
        return [
            'source' => ['required', 'string', Rule::in(VoucherKuota::CHANNELS)],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $source = (string) $this->input('source');

            if (! in_array($source, VoucherKuota::CHANNELS, true)) {
                return;
            }

            $sisa = VoucherKuota::sisa($source);

            if ($this->integer('quantity') > $sisa) {
                $validator->errors()->add(
                    'quantity',
                    $sisa > 0
                        ? 'Jumlah melebihi sisa kuota '.$sisa.' kode.'
                        : 'Kuota voucher '.$source.' sudah habis.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'source.required' => 'Pilih kanal voucher.',
            'source.in' => 'Kanal voucher tidak dikenal.',
            'quantity.required' => 'Jumlah kode wajib diisi.',
            'quantity.integer' => 'Jumlah kode harus berupa angka.',
            'quantity.min' => 'Jumlah kode minimal 1.',
        ];
    }
}
