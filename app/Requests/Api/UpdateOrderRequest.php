<?php

declare(strict_types=1);

namespace App\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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
            'status' => [
                'sometimes',
                Rule::in([
                    'ABIERTO', 'ENVIADO', 'EN_PREPARACION', 'LISTO', 'ENTREGADO', 'CERRADO', 'CANCELADO',
                ]),
            ],
            'observations' => 'nullable|string|max:5000',
            'customer_name' => 'nullable|string|max:255',
            'subtotal' => 'sometimes|numeric|min:0',
            'discount' => 'sometimes|numeric|min:0',
            'total' => 'sometimes|numeric|min:0',
        ];
    }
}
