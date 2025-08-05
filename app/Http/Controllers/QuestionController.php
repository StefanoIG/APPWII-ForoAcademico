<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Question;
use App\Services\FileUploadService;
use App\Services\MarkdownService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    use AuthorizesRequests;

    protected FileUploadService $fileUploadService;
    protected MarkdownService $markdownService;

    public function __construct(FileUploadService $fileUploadService, MarkdownService $markdownService)
    {
        $this->fileUploadService = $fileUploadService;
        $this->markdownService = $markdownService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Question::with(['user', 'category', 'tags', 'answers', 'bestAnswer', 'votes', 'attachments']);

        // Filtros
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                  ->orWhere('contenido', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('nombre', $request->tag);
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        if ($sortBy === 'votes') {
            $query->withCount(['votes as positive_votes' => function ($q) {
                $q->where('valor', 1);
            }])
            ->withCount(['votes as negative_votes' => function ($q) {
                $q->where('valor', -1);
            }])
            ->orderByRaw('positive_votes - negative_votes ' . $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $questions = $query->paginate(20);

        return response()->json($questions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request)
    {
        // El Form Request ya validó los datos
        $validated = $request->validated();

        // Usamos una transacción para asegurar la integridad de los datos
        $question = DB::transaction(function () use ($validated, $request) {
            // Crear la pregunta inicialmente
            $question = Question::create([
                'user_id' => Auth::id(),
                'category_id' => $validated['category_id'],
                'titulo' => $validated['titulo'],
                'contenido' => $validated['contenido'],
                'estado' => 'abierta',
            ]);

            // Asociar las etiquetas a la pregunta
            if (!empty($validated['tags'])) {
                $question->tags()->attach($validated['tags']);
            }

            // Procesar archivos adjuntos e imágenes
            $imageUrls = [];
            $attachmentUrls = [];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $file) {
                    $attachment = $this->fileUploadService->uploadFile($file, $question->id);
                    
                    // Si es una imagen, guardar su URL para el markdown
                    if ($attachment && $attachment->tipo === 'image') {
                        $imageUrls[$index] = $attachment->url;
                    }
                    $attachmentUrls[$index] = $attachment;
                }
            }

            // Procesar markdown después de tener las URLs de las imágenes
            $contenidoMarkdown = $validated['contenido_markdown'] ?? null;
            $contenidoHtml = null;
            
            if ($contenidoMarkdown) {
                // Reemplazar placeholders de imágenes con URLs reales
                $contenidoMarkdown = $this->processImagePlaceholders($contenidoMarkdown, $imageUrls);
                
                // Sanitizar y convertir markdown
                $contenidoMarkdown = $this->markdownService->sanitize($contenidoMarkdown);
                $contenidoHtml = $this->markdownService->toHtml($contenidoMarkdown);
                
                // Actualizar la pregunta con el contenido markdown procesado
                $question->update([
                    'contenido_markdown' => $contenidoMarkdown,
                    'contenido_html' => $contenidoHtml,
                ]);
            }

            return $question;
        });

        // Cargar relaciones para la respuesta incluyendo archivos
        $question->load('user', 'category', 'tags', 'attachments');

        return response()->json([
            'message' => 'Pregunta creada exitosamente.',
            'question' => $question,
            'attachments_uploaded' => $question->attachments->count()
        ], 201);
    }

    /**
     * Procesar placeholders de imágenes en el markdown
     */
    private function processImagePlaceholders($markdown, $imageUrls)
    {
        // Buscar patrones como ![alt](placeholder:0) o ![alt](file:0)
        $pattern = '/!\[([^\]]*)\]\((?:placeholder|file):(\d+)\)/';
        
        return preg_replace_callback($pattern, function($matches) use ($imageUrls) {
            $altText = $matches[1];
            $index = (int)$matches[2];
            
            if (isset($imageUrls[$index])) {
                // Reemplazar con la URL real de la imagen
                return "![{$altText}]({$imageUrls[$index]})";
            }
            
            // Si no hay URL, mantener el placeholder o remover
            return "![{$altText}](imagen-no-disponible)";
        }, $markdown);
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        $question->load([
            'user',
            'category',
            'tags',
            'answers.user',
            'answers.votes',
            'bestAnswer',
            'votes',
            'attachments'
        ]);

        // Incrementar vistas (opcional)
        // $question->increment('vistas');

        return response()->json($question);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $this->authorize('update', $question);

        $question = DB::transaction(function () use ($request, $question) {
            // Datos básicos a actualizar
            $updateData = $request->only(['titulo', 'contenido', 'category_id', 'estado']);

            // Actualizar tags si se proporcionan
            if ($request->filled('tags')) {
                $question->tags()->sync($request->tags);
            }

            // Eliminar archivos seleccionados
            if ($request->filled('remove_attachments')) {
                foreach ($request->remove_attachments as $attachmentId) {
                    $attachment = $question->attachments()->find($attachmentId);
                    if ($attachment) {
                        $this->fileUploadService->deleteFile($attachment);
                    }
                }
            }

            // Procesar nuevos archivos adjuntos e imágenes
            $imageUrls = [];
            $newAttachments = [];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $file) {
                    $attachment = $this->fileUploadService->uploadFile($file, $question->id);
                    
                    // Si es una imagen, guardar su URL para el markdown
                    if ($attachment && $attachment->tipo === 'image') {
                        $imageUrls[$index] = $attachment->url;
                    }
                    $newAttachments[$index] = $attachment;
                }
            }

            // Procesar markdown después de tener las URLs de las imágenes
            if ($request->filled('contenido_markdown')) {
                $contenidoMarkdown = $request->contenido_markdown;
                
                // Reemplazar placeholders de imágenes con URLs reales
                $contenidoMarkdown = $this->processImagePlaceholders($contenidoMarkdown, $imageUrls);
                
                // Sanitizar y convertir markdown
                $contenidoMarkdown = $this->markdownService->sanitize($contenidoMarkdown);
                $contenidoHtml = $this->markdownService->toHtml($contenidoMarkdown);
                
                $updateData['contenido_markdown'] = $contenidoMarkdown;
                $updateData['contenido_html'] = $contenidoHtml;
            }

            // Actualizar la pregunta
            $question->update($updateData);

            return $question;
        });

        $question->load(['user', 'category', 'tags', 'answers', 'votes', 'attachments']);

        return response()->json([
            'message' => 'Pregunta actualizada exitosamente.',
            'question' => $question,
            'attachments_count' => $question->attachments->count()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        // Solo el autor o admin puede eliminar
        if ($question->user_id !== Auth::id() && Auth::user()->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para eliminar esta pregunta.'
            ], 403);
        }

        $question->delete();

        return response()->json([
            'message' => 'Pregunta eliminada exitosamente.'
        ]);
    }

    /**
     * Agregar pregunta a favoritos
     */
    public function addToFavorites(Question $question)
    {
        $user = Auth::user();

        if ($user->favorites()->where('question_id', $question->id)->exists()) {
            return response()->json([
                'message' => 'Esta pregunta ya está en tus favoritos.'
            ], 422);
        }

        $user->favorites()->attach($question->id);

        return response()->json([
            'message' => 'Pregunta agregada a favoritos.'
        ]);
    }

    /**
     * Remover pregunta de favoritos
     */
    public function removeFromFavorites(Question $question)
    {
        $user = Auth::user();

        $user->favorites()->detach($question->id);

        return response()->json([
            'message' => 'Pregunta removida de favoritos.'
        ]);
    }

    /**
     * Obtener preguntas favoritas del usuario
     */
    public function favorites()
    {
        $user = Auth::user();
        $favorites = $user->favorites()
            ->with(['user', 'category', 'tags', 'answers', 'votes', 'attachments'])
            ->paginate(20);

        return response()->json($favorites);
    }

    /**
     * Eliminar archivo adjunto específico
     */
    public function deleteAttachment($attachmentId)
    {
        $attachment = \App\Models\QuestionAttachment::findOrFail($attachmentId);
        $question = $attachment->question;

        // Verificar permisos (solo el autor o admin)
        if ($question->user_id !== Auth::id() && Auth::user()->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para eliminar este archivo.'
            ], 403);
        }

        $this->fileUploadService->deleteFile($attachment);

        return response()->json([
            'message' => 'Archivo eliminado exitosamente.'
        ]);
    }

    /**
     * Obtener información sobre límites de archivos
     */
    public function getFileUploadInfo()
    {
        return response()->json(\App\Services\FileUploadService::getAllowedFileInfo());
    }

    /**
     * Subir imagen individualmente (para editores de markdown)
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB máximo
        ]);

        if (!$request->hasFile('image')) {
            return response()->json([
                'message' => 'No se proporcionó ninguna imagen.'
            ], 422);
        }

        try {
            // Crear una pregunta temporal o usar ID temporal
            $questionId = $request->input('question_id', 'temp_' . uniqid());
            
            $attachment = $this->fileUploadService->uploadFile(
                $request->file('image'), 
                $questionId
            );

            return response()->json([
                'message' => 'Imagen subida exitosamente.',
                'attachment' => $attachment,
                'url' => $attachment->url,
                'markdown' => "![Imagen]({$attachment->url})"
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al subir la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir múltiples imágenes para preview de markdown
     */
    public function uploadMultipleImages(Request $request)
    {
        $request->validate([
            'images' => 'required|array|max:5',
            'images.*' => 'image|max:5120', // 5MB por imagen
        ]);

        $uploadedImages = [];
        $questionId = $request->input('question_id', 'temp_' . uniqid());

        try {
            foreach ($request->file('images') as $index => $image) {
                $attachment = $this->fileUploadService->uploadFile($image, $questionId);
                
                $uploadedImages[] = [
                    'index' => $index,
                    'attachment' => $attachment,
                    'url' => $attachment->url,
                    'markdown' => "![Imagen {$index}]({$attachment->url})",
                    'placeholder' => "![Imagen {$index}](file:{$index})"
                ];
            }

            return response()->json([
                'message' => 'Imágenes subidas exitosamente.',
                'images' => $uploadedImages,
                'count' => count($uploadedImages)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al subir imágenes: ' . $e->getMessage()
            ], 500);
        }
    }
}
