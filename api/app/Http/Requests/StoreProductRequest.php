<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise le SKU avant validation : " abc-123 " et "ABC-123" sont le même produit.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('sku'))) {
            $this->merge(['sku' => strtoupper(trim($this->input('sku')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9-]+$/', $this->uniqueSkuRule()],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }

    protected function uniqueSkuRule(): Unique
    {
        return Rule::unique('products', 'sku');
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.integer' => 'La catégorie est invalide.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'name.required' => 'Le nom est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne doit pas dépasser :max caractères.',
            'sku.required' => 'Le SKU est obligatoire.',
            'sku.string' => 'Le SKU doit être une chaîne de caractères.',
            'sku.max' => 'Le SKU ne doit pas dépasser :max caractères.',
            'sku.regex' => 'Le SKU ne peut contenir que des lettres, des chiffres et des tirets.',
            'sku.unique' => 'Ce SKU est déjà utilisé par un autre produit.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.decimal' => 'Le prix doit avoir au plus 2 décimales.',
            'price.min' => 'Le prix ne peut pas être négatif.',
            'price.max' => 'Le prix ne doit pas dépasser :max.',
            'stock.required' => 'Le stock est obligatoire.',
            'stock.integer' => 'Le stock doit être un nombre entier.',
            'stock.min' => 'Le stock ne peut pas être négatif.',
            'stock.max' => 'Le stock ne doit pas dépasser :max.',
        ];
    }
}
