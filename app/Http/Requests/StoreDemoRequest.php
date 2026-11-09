<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'descripcion' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'categoria_id' => ['required', 'integer', 'in:1,2,3,4'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'descripcion' => 'descripción',
            'categoria_id' => 'categoría',
        ];
    }
}
