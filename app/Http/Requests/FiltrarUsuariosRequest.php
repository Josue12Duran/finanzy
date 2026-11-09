<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrarUsuariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Administrador') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'rol' => ['nullable', 'string', Rule::in(User::ROLES)],
            'estado' => ['nullable', 'string', Rule::in(['activo', 'inactivo'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'q' => 'búsqueda',
            'rol' => 'rol',
            'estado' => 'estado',
        ];
    }
}
