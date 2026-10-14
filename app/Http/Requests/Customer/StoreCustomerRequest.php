<?php

namespace App\Http\Requests\Customer;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('whatsapp')) {
            $this->merge(['no_wa' => PhoneNumber::normalize($this->input('whatsapp'))]);
        } elseif ($this->has('no_wa')) {
            $this->merge(['no_wa' => PhoneNumber::normalize($this->input('no_wa'))]);
        }

        if ($this->has('name')) {
            $this->merge(['nama' => $this->input('name')]);
        }

        if ($this->has('wa_consent')) {
            $this->merge(['setuju_pesan' => $this->boolean('wa_consent')]);
        } elseif ($this->has('setuju_pesan')) {
            $this->merge(['setuju_pesan' => $this->boolean('setuju_pesan')]);
        }

        if ($this->has('birth_date') && $this->filled('birth_date')) {
            try {
                $d = \Carbon\Carbon::parse($this->input('birth_date'));
                $this->merge([
                    'lahir_tgl' => (int) $d->day,
                    'lahir_bln' => (int) $d->month,
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:50'],
            'no_wa' => ['required', 'string', 'regex:/^628[0-9]{6,12}$/', 'unique:customer,no_wa'],
            'lahir_tgl' => ['nullable', 'integer', 'between:1,31', 'required_with:lahir_bln'],
            'lahir_bln' => ['nullable', 'integer', 'between:1,12', 'required_with:lahir_tgl'],
            'setuju_pesan' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_wa.regex' => 'Nomor WhatsApp tidak valid. Gunakan format 628xxxxxxxx.',
            'no_wa.unique' => 'Nomor WhatsApp sudah terdaftar.',
            'lahir_tgl.required_with' => 'Tanggal dan bulan lahir harus diisi bersamaan.',
            'lahir_bln.required_with' => 'Tanggal dan bulan lahir harus diisi bersamaan.',
        ];
    }
}
