<?php

namespace App\Http\Requests\Seller;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->shop !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:1000'],
            'bank_name' => ['required', 'string', 'max:60'],
            'bank_account' => ['required', 'string', 'regex:/^[0-9\- ]{6,30}$/'],
            'logo' => ['nullable', 'image', 'max:2048', 'dimensions:min_width=100,min_height=100'],
            'banner' => ['nullable', 'image', 'max:4096', 'dimensions:min_width=600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'description' => 'penerangan',
            'bank_name' => 'nama bank',
            'bank_account' => 'nombor akaun',
            'logo' => 'logo',
            'banner' => 'banner',
        ];
    }
}
