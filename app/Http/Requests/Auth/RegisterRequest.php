<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            // Cualquier rol menos administrador / superadministrador.
            'role' => ['required', Rule::in(array_merge(User::SELF_SIGNUP_COMMUNITY, User::SELF_SIGNUP_STAFF))],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'username' => 'usuario', 'password' => 'contraseña', 'role' => 'rol', 'phone' => 'teléfono'];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'username.unique' => 'Ese nombre de usuario ya está en uso.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role.in' => 'Elegí uno de los roles de la lista.',
        ];
    }
}
