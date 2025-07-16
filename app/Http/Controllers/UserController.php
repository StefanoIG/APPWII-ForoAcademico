<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Solo admins pueden ver la lista de usuarios
        if ($user->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para ver la lista de usuarios.'
            ], 403);
        }

        $query = User::withCount(['questions', 'answers', 'votes']);

        // Filtros
        if ($request->filled('rol')) {
            $query->where('rol', $request->rol);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        if ($sortBy === 'reputacion') {
            $query->orderBy('reputacion', $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $users = $query->paginate(20);

        return response()->json($users);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->loadCount(['questions', 'answers', 'votes']);
        
        // Cargar actividad reciente
        $recentQuestions = $user->questions()
            ->with(['category', 'tags'])
            ->latest()
            ->take(5)
            ->get();

        $recentAnswers = $user->answers()
            ->with(['question'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'user' => $user,
            'recent_questions' => $recentQuestions,
            'recent_answers' => $recentAnswers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();

        // Solo el propio usuario o admin puede actualizar
        if ($user->id !== $currentUser->id && $currentUser->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para actualizar este usuario.'
            ], 403);
        }

        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
        ];

        // Solo admins pueden cambiar roles
        if ($currentUser->rol === 'admin') {
            $rules['rol'] = 'sometimes|required|in:usuario,moderador,admin';
        }

        // Solo el propio usuario puede cambiar su contraseña
        if ($user->id === $currentUser->id) {
            $rules['password'] = 'sometimes|required|string|min:8|confirmed';
        }

        $request->validate($rules);

        $updateData = $request->only(['name', 'email']);

        if ($currentUser->rol === 'admin' && $request->filled('rol')) {
            $updateData['rol'] = $request->rol;
        }

        if ($user->id === $currentUser->id && $request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'Usuario actualizado exitosamente.',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $currentUser = Auth::user();

        // Solo admins pueden eliminar usuarios (y no pueden eliminarse a sí mismos)
        if ($currentUser->rol !== 'admin' || $user->id === $currentUser->id) {
            return response()->json([
                'message' => 'No tienes permisos para eliminar este usuario.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente.'
        ]);
    }

    /**
     * Get user profile (current user)
     */
    public function profile()
    {
        $user = Auth::user();

        return response()->json([
            'user' => $user,
            'message' => 'Perfil obtenido exitosamente'
        ]);
    }

    /**
     * Get user questions
     */
    public function questions(User $user)
    {
        $questions = $user->questions()
            ->with(['category', 'tags', 'answers', 'votes'])
            ->latest()
            ->paginate(20);

        return response()->json($questions);
    }

    /**
     * Get user answers
     */
    public function answers(User $user)
    {
        $answers = $user->answers()
            ->with(['question.category', 'votes'])
            ->latest()
            ->paginate(20);

        return response()->json($answers);
    }

    /**
     * Get users leaderboard by reputation
     */
    public function leaderboard()
    {
        $users = User::select(['id', 'name', 'reputacion'])
            ->withCount(['questions', 'answers'])
            ->orderBy('reputacion', 'desc')
            ->take(50)
            ->get();

        return response()->json($users);
    }

    /**
     * Get user statistics
     */
    public function statistics()
    {
        $currentUser = Auth::user();

        // Solo admins pueden ver estadísticas generales
        if ($currentUser->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para ver las estadísticas.'
            ], 403);
        }

        $stats = [
            'total_users' => User::count(),
            'admins' => User::where('rol', 'admin')->count(),
            'moderators' => User::where('rol', 'moderador')->count(),
            'regular_users' => User::where('rol', 'usuario')->count(),
            'users_with_questions' => User::has('questions')->count(),
            'users_with_answers' => User::has('answers')->count(),
            'top_reputation' => User::orderBy('reputacion', 'desc')->take(10)->get(['name', 'reputacion']),
        ];

        return response()->json($stats);
    }
}
