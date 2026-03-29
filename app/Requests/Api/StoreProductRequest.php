<?php

declare(strict_types=1);

namespace App\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de alta de producto vía API JSON.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['PRODUCT', 'INSUMO'])],
            'category_id' => [
                'required_if:type,PRODUCT',
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($q) => $q->where('restaurant_id', (int) $this->user()?->restaurant_id)
                ),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price' => 'required_if:type,PRODUCT|nullable|numeric|min:0',
            'image' => 'nullable|string|max:500',
            'has_stock' => 'sometimes|boolean',
            'stock_minimum' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'unit' => 'required_if:type,INSUMO|nullable|string|max:50',
            'unit_cost' => 'nullable|numeric|min:0',
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where(
                    fn ($q) => $q->where('restaurant_id', (int) $this->user()?->restaurant_id)
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_stock' => $this->boolean('has_stock'),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
