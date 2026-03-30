<?php

declare(strict_types=1);

namespace App\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de actualización parcial de producto (API).
 */
class UpdateProductRequest extends FormRequest
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
            'type' => ['sometimes', Rule::in(['PRODUCT', 'INSUMO'])],
            'category_id' => 'sometimes|nullable|integer|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price' => 'sometimes|nullable|numeric|min:0',
            'image' => 'nullable|string|max:500',
            'has_stock' => 'sometimes|boolean',
            'stock_minimum' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'unit' => 'nullable|string|max:50',
            'unit_cost' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
        ];
    }
}
