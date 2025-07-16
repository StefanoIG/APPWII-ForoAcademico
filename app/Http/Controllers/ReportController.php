<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Solo admins y moderadores pueden ver todos los reportes
        if (!in_array($user->rol, ['admin', 'moderador'])) {
            return response()->json([
                'message' => 'No tienes permisos para ver los reportes.'
            ], 403);
        }

        $query = Report::with(['user', 'reportable']);

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('motivo')) {
            $query->where('motivo', $request->motivo);
        }

        if ($request->filled('reportable_type')) {
            $query->where('reportable_type', $request->reportable_type);
        }

        $reports = $query->latest()->paginate(20);

        return response()->json($reports);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReportRequest $request)
    {
        $user = Auth::user();

        // Verificar que el contenido reportable existe
        $reportableClass = $request->reportable_type;
        $reportable = $reportableClass::findOrFail($request->reportable_id);

        // Verificar que no reporte su propio contenido
        if ($reportable->user_id === $user->id) {
            return response()->json([
                'message' => 'No puedes reportar tu propio contenido.'
            ], 422);
        }

        // Verificar si ya reportó este contenido
        $existingReport = Report::where('user_id', $user->id)
            ->where('reportable_type', $request->reportable_type)
            ->where('reportable_id', $request->reportable_id)
            ->first();

        if ($existingReport) {
            return response()->json([
                'message' => 'Ya has reportado este contenido.'
            ], 422);
        }

        $report = Report::create([
            'user_id' => $user->id,
            'reportable_type' => $request->reportable_type,
            'reportable_id' => $request->reportable_id,
            'motivo' => $request->motivo,
            'descripcion' => $request->descripcion,
            'estado' => 'pendiente',
        ]);

        $report->load(['user', 'reportable']);

        return response()->json([
            'message' => 'Reporte enviado exitosamente.',
            'report' => $report
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Report $report)
    {
        $user = Auth::user();

        // Solo admins, moderadores y el reportador pueden ver el reporte
        if (!in_array($user->rol, ['admin', 'moderador']) && $report->user_id !== $user->id) {
            return response()->json([
                'message' => 'No tienes permisos para ver este reporte.'
            ], 403);
        }

        $report->load(['user', 'reportable']);
        return response()->json($report);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Report $report)
    {
        $user = Auth::user();

        // Solo admins y moderadores pueden actualizar reportes
        if (!in_array($user->rol, ['admin', 'moderador'])) {
            return response()->json([
                'message' => 'No tienes permisos para actualizar reportes.'
            ], 403);
        }

        $request->validate([
            'estado' => 'required|in:pendiente,revisado,descartado',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $report->update([
            'estado' => $request->estado,
            'observaciones' => $request->observaciones,
            'revisado_por' => $user->id,
            'revisado_en' => now(),
        ]);

        $report->load(['user', 'reportable']);

        return response()->json([
            'message' => 'Reporte actualizado exitosamente.',
            'report' => $report
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report)
    {
        $user = Auth::user();

        // Solo admins pueden eliminar reportes
        if ($user->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para eliminar reportes.'
            ], 403);
        }

        $report->delete();

        return response()->json([
            'message' => 'Reporte eliminado exitosamente.'
        ]);
    }

    /**
     * Get statistics for reports
     */
    public function statistics()
    {
        $user = Auth::user();

        // Solo admins y moderadores pueden ver estadísticas
        if (!in_array($user->rol, ['admin', 'moderador'])) {
            return response()->json([
                'message' => 'No tienes permisos para ver las estadísticas.'
            ], 403);
        }

        $stats = [
            'total' => Report::count(),
            'pendientes' => Report::where('estado', 'pendiente')->count(),
            'revisados' => Report::where('estado', 'revisado')->count(),
            'descartados' => Report::where('estado', 'descartado')->count(),
            'por_motivo' => Report::selectRaw('motivo, COUNT(*) as total')
                ->groupBy('motivo')
                ->get(),
            'por_tipo' => Report::selectRaw('reportable_type, COUNT(*) as total')
                ->groupBy('reportable_type')
                ->get(),
        ];

        return response()->json($stats);
    }
}
