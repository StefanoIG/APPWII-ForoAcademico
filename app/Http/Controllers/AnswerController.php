<?php

namespace App\Http\Controllers;

use App\Events\BestAnswerMarked;
use App\Http\Requests\StoreAnswerRequest;
use App\Http\Requests\UpdateAnswerRequest;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnswerController extends Controller
{
    use AuthorizesRequests;
    public function markAsBest(Request $request, Answer $answer)
    {
        $question = $answer->question;

        // Usamos la policy para verificar si el usuario actual es el autor de la pregunta
        $this->authorize('update', $question);

        // Actualizamos la pregunta
        $question->mejor_respuesta_id = $answer->id;
        $question->estado = 'resuelta';
        $question->save();

        // Disparar evento para dar reputación (+10) al autor de la respuesta
        event(new BestAnswerMarked($answer));

        return response()->json(['message' => 'Respuesta marcada como la mejor.']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $answers = Answer::with(['user', 'question', 'votes'])
            ->latest()
            ->paginate(20);

        return response()->json($answers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAnswerRequest $request)
    {
        $user = Auth::user();
        $question = Question::findOrFail($request->question_id);

        // Verificar si el usuario ya respondió esta pregunta
        $existingAnswer = Answer::where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();

        if ($existingAnswer) {
            return response()->json([
                'message' => 'Ya has respondido esta pregunta. Puedes editar tu respuesta existente.'
            ], 422);
        }

        // Verificar que la pregunta esté abierta
        if ($question->estado !== 'abierta') {
            return response()->json([
                'message' => 'No se pueden agregar respuestas a esta pregunta.'
            ], 422);
        }

        $answer = Answer::create([
            'contenido' => $request->contenido,
            'question_id' => $request->question_id,
            'user_id' => $user->id,
        ]);

        $answer->load(['user', 'question', 'votes']);

        return response()->json([
            'message' => 'Respuesta creada exitosamente.',
            'answer' => $answer
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Answer $answer)
    {
        $answer->load(['user', 'question', 'votes']);
        return response()->json($answer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAnswerRequest $request, Answer $answer)
    {
        // Verificar que el usuario sea el autor de la respuesta
        if ($answer->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'No tienes permisos para editar esta respuesta.'
            ], 403);
        }

        $answer->update([
            'contenido' => $request->contenido,
        ]);

        $answer->load(['user', 'question', 'votes']);

        return response()->json([
            'message' => 'Respuesta actualizada exitosamente.',
            'answer' => $answer
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Answer $answer)
    {
        // Verificar que el usuario sea el autor de la respuesta o admin
        if ($answer->user_id !== Auth::id() && Auth::user()->rol !== 'admin') {
            return response()->json([
                'message' => 'No tienes permisos para eliminar esta respuesta.'
            ], 403);
        }

        // Si es la mejor respuesta, actualizar la pregunta
        if ($answer->question->mejor_respuesta_id === $answer->id) {
            $answer->question->update([
                'mejor_respuesta_id' => null,
                'estado' => 'abierta'
            ]);
        }

        $answer->delete();

        return response()->json([
            'message' => 'Respuesta eliminada exitosamente.'
        ]);
    }
}
