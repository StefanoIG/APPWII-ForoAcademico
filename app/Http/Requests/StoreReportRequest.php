<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
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
            'motivo' => 'required|in:spam,ofensivo,inapropiado,otro',
            'descripcion' => 'nullable|string|max:500',
            'reportable_type' => 'required|in:App\Models\Question,App\Models\Answer',
            'reportable_id' => 'required|integer',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Debe especificar el motivo del reporte.',
            'motivo.in' => 'El motivo debe ser: spam, ofensivo, inapropiado u otro.',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres.',
            'reportable_type.required' => 'Debe especificar el tipo de contenido a reportar.',
            'reportable_type.in' => 'Solo se pueden reportar preguntas o respuestas.',
            'reportable_id.required' => 'Debe especificar el ID del contenido a reportar.',
            'reportable_id.integer' => 'El ID debe ser un número entero.',
        ];
    }
}
