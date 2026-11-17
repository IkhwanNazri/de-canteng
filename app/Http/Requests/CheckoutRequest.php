<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^(\+?6?0)[0-9\- ]{8,12}$/'],
            'address' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'digits:5'],
            'city' => ['required', 'string', 'max:60'],
            'state' => ['required', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'recipient_name' => 'nama penerima',
            'phone' => 'nombor telefon',
            'address' => 'alamat',
            'postcode' => 'poskod',
            'city' => 'bandar',
            'state' => 'negeri',
        ];
    }
}
