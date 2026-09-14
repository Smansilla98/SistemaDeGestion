<?php

declare(strict_types=1);

namespace App\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
        $restaurantId = (int) $this->user()?->restaurant_id;

        return [
            'table_id' => [
                'nullable',
                'integer',
                Rule::exists('tables', 'id')->where(fn ($q) => $q->where('restaurant_id', $restaurantId)),
            ],
            'subsector_item_id' => 'nullable|integer|exists:subsector_items,id',
            'observations' => 'nullable|string|max:5000',
            'customer_name' => 'nullable|string|max:255',
            'idempotency_key' => 'nullable|string|max:64',
            'ensure_table_occupied' => 'nullable|boolean',
            'send_to_kitchen' => 'nullable|boolean',
            'items' => 'nullable|array|min:1',
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ];
    }
}
