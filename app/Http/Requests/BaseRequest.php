<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * Prepare the data for validation.
     * Sanitiza los inputs para prevenir XSS
     */
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        // Sanitizar strings para prevenir XSS
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Escapar HTML pero mantener caracteres especiales necesarios
                $value = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
                // Limpiar caracteres de control
                $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
            }
        });

        $this->merge($input);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson() || $this->wantsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'email' => 'El campo :attribute debe ser un email válido.',
            'unique' => 'El :attribute ya está en uso.',
            'exists' => 'El :attribute seleccionado no es válido.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
        ];
    }
}

