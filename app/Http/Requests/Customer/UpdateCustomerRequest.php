<?php

namespace App\Http\Requests\Customer;

use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends StoreCustomerRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['no_wa'] = [
            'required',
            'string',
            'regex:/^628[0-9]{6,12}$/',
            Rule::unique('customer', 'no_wa')->ignore(
                $this->route('customer')->id_customer,
                'id_customer'
            ),
        ];

        return $rules;
    }
}
