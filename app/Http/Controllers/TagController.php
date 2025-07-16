<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Tag::withCount('questions');

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nombre', 'like', "%{$search}%");
        }

        // Ordenamiento
        $sortBy = $request->get('sort', 'nombre');
        $sortDirection = $request->get('direction', 'asc');

        if ($sortBy === 'popularity') {
            $query->orderBy('questions_count', 'desc');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $tags = $query->paginate(50);

        return response()->json($tags);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Solo admins pueden crear tags
        if ($user->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para crear etiquetas.'
            ], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255|unique:tags,nombre',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $tag = Tag::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'message' => 'Etiqueta creada exitosamente.',
            'tag' => $tag
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag)
    {
        $tag->loadCount('questions');
        return response()->json($tag);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tag $tag)
    {
        $user = Auth::user();

        // Solo admins pueden actualizar tags
        if ($user->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para actualizar etiquetas.'
            ], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255|unique:tags,nombre,' . $tag->id,
            'descripcion' => 'nullable|string|max:500',
        ]);

        $tag->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'message' => 'Etiqueta actualizada exitosamente.',
            'tag' => $tag
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        $user = Auth::user();

        // Solo admins pueden eliminar tags
        if ($user->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para eliminar etiquetas.'
            ], 403);
        }

        // Verificar que no tenga preguntas asociadas
        if ($tag->questions()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar una etiqueta que tiene preguntas asociadas.'
            ], 422);
        }

        $tag->delete();

        return response()->json([
            'message' => 'Etiqueta eliminada exitosamente.'
        ]);
    }

    /**
     * Get questions for a specific tag
     */
    public function questions(Tag $tag, Request $request)
    {
        $questions = $tag->questions()
            ->with(['user', 'category', 'tags', 'answers', 'votes'])
            ->latest()
            ->paginate(20);

        return response()->json($questions);
    }

    /**
     * Get popular tags
     */
    public function popular()
    {
        $popularTags = Tag::withCount('questions')
            ->orderBy('questions_count', 'desc')
            ->take(20)
            ->get();

        return response()->json($popularTags);
    }
}
