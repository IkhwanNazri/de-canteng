<?php

namespace App\Http\Requests\Seller;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLiveSessionRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'product_ids' => ['required', 'array', 'min:1', 'max:30'],
            'product_ids.*' => ['integer', Rule::exists('products', 'id')->where('shop_id', $this->user()->shop?->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'tajuk', 'product_ids' => 'produk'];
    }
}
