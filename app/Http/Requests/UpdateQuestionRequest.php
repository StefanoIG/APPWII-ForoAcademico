<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
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
            'titulo' => 'sometimes|required|string|max:255',
            'contenido' => 'sometimes|required|string|min:10',
            'contenido_markdown' => 'nullable|string|min:10', // Contenido en markdown
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'tags' => 'sometimes|required|array|min:1',
            'tags.*' => 'integer|exists:tags,id',
            'estado' => 'sometimes|in:abierta,resuelta,cerrada',
            'attachments' => 'nullable|array|max:5', // Máximo 5 archivos
            'attachments.*' => 'file|max:10240|mimes:jpeg,jpg,png,gif,pdf,doc,docx,txt,mp4,mp3',
            'remove_attachments' => 'nullable|array', // IDs de archivos a eliminar
            'remove_attachments.*' => 'integer|exists:question_attachments,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'titulo.max' => 'El título no puede exceder los 255 caracteres.',
            'contenido.required' => 'El contenido es obligatorio.',
            'contenido.min' => 'El contenido debe tener al menos 10 caracteres.',
            'category_id.required' => 'Debe seleccionar una categoría.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'tags.required' => 'Debe seleccionar al menos una etiqueta.',
            'tags.min' => 'Debe seleccionar al menos una etiqueta.',
            'tags.*.exists' => 'Una o más etiquetas seleccionadas no existen.',
            'estado.in' => 'El estado debe ser: abierta, resuelta o cerrada.',
            'attachments.max' => 'No puede subir más de 5 archivos.',
            'attachments.*.file' => 'Uno de los archivos no es válido.',
            'attachments.*.max' => 'Uno de los archivos excede el tamaño máximo de 10MB.',
            'attachments.*.mimes' => 'Uno de los archivos tiene un formato no permitido.',
            'remove_attachments.*.exists' => 'Uno de los archivos a eliminar no existe.',
        ];
    }
}
