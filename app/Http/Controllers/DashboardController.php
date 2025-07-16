<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard statistics
     */
    public function adminStats()
    {
        $user = Auth::user();

        // Solo admins pueden ver las estadísticas
        if ($user->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para ver las estadísticas del dashboard.'
            ], 403);
        }

        $stats = [
            // Conteos generales
            'users' => [
                'total' => User::count(),
                'admins' => User::where('rol', 'admin')->count(),
                'moderators' => User::where('rol', 'moderador')->count(),
                'regular' => User::where('rol', 'usuario')->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
            ],
            
            'content' => [
                'questions' => [
                    'total' => Question::count(),
                    'open' => Question::where('estado', 'abierta')->count(),
                    'resolved' => Question::where('estado', 'resuelta')->count(),
                    'closed' => Question::where('estado', 'cerrada')->count(),
                    'new_today' => Question::whereDate('created_at', today())->count(),
                ],
                'answers' => [
                    'total' => Answer::count(),
                    'new_today' => Answer::whereDate('created_at', today())->count(),
                ],
                'categories' => Category::count(),
                'tags' => Tag::count(),
            ],

            'engagement' => [
                'votes' => [
                    'total' => Vote::count(),
                    'positive' => Vote::where('valor', 1)->count(),
                    'negative' => Vote::where('valor', -1)->count(),
                ],
                'reports' => [
                    'total' => Report::count(),
                    'pending' => Report::where('estado', 'pendiente')->count(),
                    'reviewed' => Report::where('estado', 'revisado')->count(),
                    'discarded' => Report::where('estado', 'descartado')->count(),
                ],
            ],

            // Top usuarios por reputación
            'top_users' => User::orderBy('reputacion', 'desc')
                ->take(10)
                ->get(['id', 'name', 'reputacion']),

            // Categorías más populares
            'popular_categories' => Category::withCount('questions')
                ->orderBy('questions_count', 'desc')
                ->take(10)
                ->get(['id', 'nombre', 'questions_count']),

            // Tags más usados
            'popular_tags' => Tag::withCount('questions')
                ->orderBy('questions_count', 'desc')
                ->take(10)
                ->get(['id', 'nombre', 'questions_count']),

            // Actividad reciente
            'recent_activity' => [
                'questions' => Question::with(['user', 'category'])
                    ->latest()
                    ->take(5)
                    ->get(['id', 'titulo', 'user_id', 'category_id', 'created_at']),
                'answers' => Answer::with(['user', 'question'])
                    ->latest()
                    ->take(5)
                    ->get(['id', 'user_id', 'question_id', 'created_at']),
            ],

            // Estadísticas por mes (últimos 6 meses)
            'monthly_stats' => $this->getMonthlyStats(),
        ];

        return response()->json($stats);
    }

    /**
     * Get general statistics (public)
     */
    public function publicStats()
    {
        $stats = [
            'total_questions' => Question::count(),
            'total_answers' => Answer::count(),
            'total_users' => User::count(),
            'resolved_questions' => Question::where('estado', 'resuelta')->count(),
            'total_votes' => Vote::count(),
            'categories_count' => Category::count(),
            'tags_count' => Tag::count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get monthly statistics for charts
     */
    private function getMonthlyStats()
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'questions' => Question::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'answers' => Answer::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'users' => User::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'votes' => Vote::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        }

        return $months;
    }

    /**
     * Get user activity overview
     */
    public function userActivity()
    {
        $user = Auth::user();

        $activity = [
            'questions' => [
                'total' => $user->questions()->count(),
                'resolved' => $user->questions()->where('estado', 'resuelta')->count(),
                'recent' => $user->questions()->latest()->take(5)->get(['id', 'titulo', 'estado', 'created_at']),
            ],
            'answers' => [
                'total' => $user->answers()->count(),
                'best_answers' => Answer::where('user_id', $user->id)
                    ->whereHas('question', function ($q) use ($user) {
                        $q->where('mejor_respuesta_id', '!=', null);
                    })->count(),
                'recent' => $user->answers()->with('question')->latest()->take(5)->get(),
            ],
            'votes_given' => Vote::where('user_id', $user->id)->count(),
            'reputation' => $user->reputacion,
            'favorites_count' => $user->favorites()->count(),
        ];

        return response()->json($activity);
    }
}
