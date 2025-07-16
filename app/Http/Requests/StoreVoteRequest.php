<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Validación de autorización se maneja en el controlador
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'valor' => 'required|in:1,-1',
            'votable_type' => 'required|in:App\Models\Question,App\Models\Answer',
            'votable_id' => 'required|integer',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'valor.required' => 'El valor del voto es obligatorio.',
            'valor.in' => 'El voto debe ser positivo (1) o negativo (-1).',
            'votable_type.required' => 'Debe especificar el tipo de contenido a votar.',
            'votable_type.in' => 'Solo se pueden votar preguntas o respuestas.',
            'votable_id.required' => 'Debe especificar el ID del contenido a votar.',
            'votable_id.integer' => 'El ID debe ser un número entero.',
        ];
    }
}
