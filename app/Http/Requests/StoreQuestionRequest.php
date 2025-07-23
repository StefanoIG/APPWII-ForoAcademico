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
            'contenido_markdown' => 'nullable|string|min:10', // Contenido en markdown
            'category_id' => 'required|integer|exists:categories,id',
            'tags' => 'required|array|min:1', // Debe tener al menos una etiqueta
            'tags.*' => 'integer|exists:tags,id', // Cada elemento del array debe ser un ID de tag válido
            'attachments' => 'nullable|array|max:5', // Máximo 5 archivos
            'attachments.*' => 'file|max:10240|mimes:jpeg,jpg,png,gif,pdf,doc,docx,txt,mp4,mp3', // 10MB máximo
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'titulo.max' => 'El título no puede exceder 255 caracteres.',
            'contenido.required' => 'El contenido es obligatorio.',
            'contenido.min' => 'El contenido debe tener al menos 10 caracteres.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
            'tags.required' => 'Debe seleccionar al menos una etiqueta.',
            'tags.min' => 'Debe seleccionar al menos una etiqueta.',
            'tags.*.exists' => 'Una o más etiquetas seleccionadas no son válidas.',
            'attachments.max' => 'No puede subir más de 5 archivos.',
            'attachments.*.file' => 'Uno de los archivos no es válido.',
            'attachments.*.max' => 'Uno de los archivos excede el tamaño máximo de 10MB.',
            'attachments.*.mimes' => 'Uno de los archivos tiene un formato no permitido.',
        ];
    }
}
