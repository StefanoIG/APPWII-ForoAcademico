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

        // Procesar markdown si se proporciona
        $contenidoMarkdown = $validated['contenido_markdown'] ?? null;
        $contenidoHtml = null;
        
        if ($contenidoMarkdown) {
            $contenidoMarkdown = $this->markdownService->sanitize($contenidoMarkdown);
            $contenidoHtml = $this->markdownService->toHtml($contenidoMarkdown);
        }

        // Usamos una transacción para asegurar la integridad de los datos
        $question = DB::transaction(function () use ($validated, $request, $contenidoMarkdown, $contenidoHtml) {
            $question = Question::create([
                'user_id' => Auth::id(),
                'category_id' => $validated['category_id'],
                'titulo' => $validated['titulo'],
                'contenido' => $validated['contenido'],
                'contenido_markdown' => $contenidoMarkdown,
                'contenido_html' => $contenidoHtml,
                'estado' => 'abierta',
            ]);

            // Asocia las etiquetas a la pregunta
            $question->tags()->attach($validated['tags']);

            // Procesar archivos adjuntos si existen
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $this->fileUploadService->uploadFile($file, $question->id);
                }
            }

            return $question;
        });

        // Cargar relaciones para la respuesta incluyendo archivos
        $question->load('user', 'category', 'tags', 'attachments');

        return response()->json([
            'message' => 'Pregunta creada exitosamente.',
            'question' => $question
        ], 201);
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

        // Procesar markdown si se proporciona
        $updateData = $request->only(['titulo', 'contenido', 'category_id', 'estado']);
        
        if ($request->filled('contenido_markdown')) {
            $contenidoMarkdown = $this->markdownService->sanitize($request->contenido_markdown);
            $updateData['contenido_markdown'] = $contenidoMarkdown;
            $updateData['contenido_html'] = $this->markdownService->toHtml($contenidoMarkdown);
        }

        $question = DB::transaction(function () use ($request, $question, $updateData) {
            $question->update($updateData);

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

            // Agregar nuevos archivos
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $this->fileUploadService->uploadFile($file, $question->id);
                }
            }

            return $question;
        });

        $question->load(['user', 'category', 'tags', 'answers', 'votes', 'attachments']);

        return response()->json([
            'message' => 'Pregunta actualizada exitosamente.',
            'question' => $question
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
}
