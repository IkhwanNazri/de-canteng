<?php

namespace App\Http\Requests\Seller;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:1', 'max:100000'],
            'compare_price' => ['nullable', 'numeric', 'gt:price', 'max:100000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sizes' => ['nullable', 'array', 'max:10'],
            'sizes.*' => ['string', 'max:20', 'distinct'],
            'colors' => ['nullable', 'array', 'max:10'],
            'colors.*' => ['string', 'max:30', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama produk',
            'category_id' => 'kategori',
            'price' => 'harga',
            'compare_price' => 'harga asal',
            'stock' => 'stok',
            'image' => 'gambar',
            'sizes' => 'saiz',
            'colors' => 'warna',
        ];
    }

    /**
     * Accept "S, M, L" style input as well as arrays, and drop empty values.
     */
    protected function prepareForValidation(): void
    {
        foreach (['sizes', 'colors'] as $field) {
            $value = $this->input($field);
            $items = is_string($value) ? explode(',', $value) : (array) $value;
            $items = array_values(array_filter(array_map('trim', $items), fn ($item) => $item !== ''));
            $this->merge([$field => $items ?: null]);
        }
    }
}
