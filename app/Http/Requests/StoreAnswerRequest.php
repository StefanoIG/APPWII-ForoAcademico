<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnswerRequest extends FormRequest
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
            'contenido' => 'required|string|min:30',
            'contenido_markdown' => 'nullable|string|min:30', // Contenido en markdown
            'question_id' => 'required|exists:questions,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contenido.required' => 'El contenido de la respuesta es obligatorio.',
            'contenido.min' => 'La respuesta debe tener al menos 30 caracteres.',
            'contenido_markdown.min' => 'El contenido markdown debe tener al menos 30 caracteres.',
            'question_id.required' => 'Debe especificar la pregunta a responder.',
            'question_id.exists' => 'La pregunta especificada no existe.',
        ];
    }
}
