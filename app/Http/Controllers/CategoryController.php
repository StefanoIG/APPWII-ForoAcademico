<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::withCount('questions')
            ->orderBy('nombre')
            ->get();

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Solo admins pueden crear categorías
        if ($user->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para crear categorías.'
            ], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255|unique:categories,nombre',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $category = Category::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'message' => 'Categoría creada exitosamente.',
            'category' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $category->loadCount('questions');
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $user = Auth::user();

        // Solo admins pueden actualizar categorías
        if ($user->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para actualizar categorías.'
            ], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255|unique:categories,nombre,' . $category->id,
            'descripcion' => 'nullable|string|max:500',
        ]);

        $category->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'message' => 'Categoría actualizada exitosamente.',
            'category' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $user = Auth::user();

        // Solo admins pueden eliminar categorías
        if ($user->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para eliminar categorías.'
            ], 403);
        }

        // Verificar que no tenga preguntas asociadas
        if ($category->questions()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar una categoría que tiene preguntas asociadas.'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoría eliminada exitosamente.'
        ]);
    }

    /**
     * Get questions for a specific category
     */
    public function questions(Category $category, Request $request)
    {
        $questions = $category->questions()
            ->with(['user', 'tags', 'answers', 'votes'])
            ->latest()
            ->paginate(20);

        return response()->json($questions);
    }
}
