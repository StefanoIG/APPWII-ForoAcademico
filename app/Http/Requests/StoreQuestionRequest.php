<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cualquiera que esté autenticado puede crear una pregunta
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string|min:10',
            'category_id' => 'required|integer|exists:categories,id',
            'tags' => 'required|array|min:1', // Debe tener al menos una etiqueta
            'tags.*' => 'integer|exists:tags,id', // Cada elemento del array debe ser un ID de tag válido
        ];
    }
}
