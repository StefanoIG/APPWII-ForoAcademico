<?php

namespace App\Http\Controllers;

use App\Events\VoteCasted;
use App\Http\Requests\StoreVoteRequest;
use App\Models\Vote;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteController extends Controller
{
    /**
     * Vote on a question (legacy method)
     */
    public function vote(Request $request, Question $question)
    {
        $request->validate(['tipo' => 'required|in:up,down']);

        // Convertir tipo a valor numérico
        $valor = $request->tipo === 'up' ? 1 : -1;

        // Verificar que no vote su propio contenido
        if ($question->user_id === Auth::id()) {
            return response()->json([
                'message' => 'No puedes votar tu propio contenido.'
            ], 422);
        }

        // updateOrCreate previene votos duplicados
        $vote = Vote::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'votable_id' => $question->id,
                'votable_type' => Question::class,
            ],
            [
                'valor' => $valor,
            ]
        );
        
        // Disparamos el evento
        event(new VoteCasted($vote, $vote->wasRecentlyCreated ? 'created' : 'updated'));

        return response()->json(['message' => 'Voto registrado.']);
    }

    /**
     * Store a newly created vote.
     */
    public function store(StoreVoteRequest $request)
    {
        $user = Auth::user();
        
        // Determinar el modelo votable
        $votableClass = $request->votable_type;
        $votable = $votableClass::findOrFail($request->votable_id);

        // Verificar que el usuario no vote su propio contenido
        if ($votable->user_id === $user->id) {
            return response()->json([
                'message' => 'No puedes votar tu propio contenido.'
            ], 422);
        }

        // Verificar si ya existe un voto del usuario para este contenido
        $existingVote = Vote::where('user_id', $user->id)
            ->where('votable_type', $request->votable_type)
            ->where('votable_id', $request->votable_id)
            ->first();

        if ($existingVote) {
            // Si es el mismo valor, eliminar el voto
            if ($existingVote->valor == $request->valor) {
                $existingVote->delete();
                
                return response()->json([
                    'message' => 'Voto eliminado.',
                    'action' => 'removed'
                ]);
            } else {
                // Si es diferente, actualizar el voto
                $existingVote->update(['valor' => $request->valor]);
                
                // Disparar evento para actualizar reputación
                event(new VoteCasted($existingVote, 'updated'));
                
                return response()->json([
                    'message' => 'Voto actualizado.',
                    'vote' => $existingVote,
                    'action' => 'updated'
                ]);
            }
        }

        // Crear nuevo voto
        $vote = Vote::create([
            'user_id' => $user->id,
            'votable_type' => $request->votable_type,
            'votable_id' => $request->votable_id,
            'valor' => $request->valor,
        ]);

        // Disparar evento para actualizar reputación
        event(new VoteCasted($vote, 'created'));

        return response()->json([
            'message' => 'Voto registrado exitosamente.',
            'vote' => $vote,
            'action' => 'created'
        ], 201);
    }

    /**
     * Get vote statistics for a votable item.
     */
    public function getVoteStats(Request $request)
    {
        $request->validate([
            'votable_type' => 'required|in:App\Models\Question,App\Models\Answer',
            'votable_id' => 'required|integer',
        ]);

        $positiveVotes = Vote::where('votable_type', $request->votable_type)
            ->where('votable_id', $request->votable_id)
            ->where('valor', 1)
            ->count();

        $negativeVotes = Vote::where('votable_type', $request->votable_type)
            ->where('votable_id', $request->votable_id)
            ->where('valor', -1)
            ->count();

        $userVote = null;
        if (Auth::check()) {
            $userVote = Vote::where('user_id', Auth::id())
                ->where('votable_type', $request->votable_type)
                ->where('votable_id', $request->votable_id)
                ->first();
        }

        return response()->json([
            'positive_votes' => $positiveVotes,
            'negative_votes' => $negativeVotes,
            'total_score' => $positiveVotes - $negativeVotes,
            'user_vote' => $userVote ? $userVote->valor : null,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $votes = Vote::with(['user', 'votable'])
            ->latest()
            ->paginate(20);

        return response()->json($votes);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vote $vote)
    {
        $vote->load(['user', 'votable']);
        return response()->json($vote);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vote $vote)
    {
        $request->validate([
            'valor' => 'required|in:1,-1',
        ]);

        // Verificar que el usuario sea el autor del voto
        if ($vote->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'No tienes permisos para actualizar este voto.'
            ], 403);
        }

        $vote->update(['valor' => $request->valor]);

        // Disparar evento para actualizar reputación
        event(new VoteCasted($vote, 'updated'));

        return response()->json([
            'message' => 'Voto actualizado exitosamente.',
            'vote' => $vote
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vote $vote)
    {
        // Verificar que el usuario sea el autor del voto
        if ($vote->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'No tienes permisos para eliminar este voto.'
            ], 403);
        }

        $vote->delete();

        return response()->json([
            'message' => 'Voto eliminado exitosamente.'
        ]);
    }
}
