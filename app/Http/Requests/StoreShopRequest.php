<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->shop === null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:60', 'unique:shops,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'bank_name' => ['required', 'string', 'max:60'],
            'bank_account' => ['required', 'string', 'regex:/^[0-9\- ]{6,30}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kedai',
            'description' => 'penerangan',
            'bank_name' => 'nama bank',
            'bank_account' => 'nombor akaun',
        ];
    }
}
