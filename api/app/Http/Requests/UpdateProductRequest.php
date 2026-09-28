<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Unique;

class UpdateProductRequest extends StoreProductRequest
{
    /**
     * Mêmes règles qu'à la création ; en PATCH, seuls les champs envoyés sont validés.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        if ($this->isMethod('patch')) {
            $rules = array_map(fn (array $fieldRules) => ['sometimes', ...$fieldRules], $rules);
        }

        return $rules;
    }

    /**
     * Le produit mis à jour peut conserver son propre SKU.
     */
    protected function uniqueSkuRule(): Unique
    {
        return parent::uniqueSkuRule()->ignore($this->route('product'));
    }
}
