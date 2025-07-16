<?php

namespace App\Listeners;

use App\Events\VoteCasted;
use App\Models\User;

class UpdateReputation
{
    public function handle(VoteCasted $event): void
    {
        $vote = $event->vote;
        $action = $event->action ?? 'created'; // created, updated, deleted
        $target = $vote->votable; // La pregunta o respuesta votada
        $author = $target->user;  // El autor del contenido

        // Evitar que el autor se dé reputación a sí mismo
        if ($author->id === $vote->user_id) {
            return;
        }

        $points = 0;
        
        // Calcular puntos según el valor del voto
        if ($vote->valor == 1) { // Voto positivo
            $points = 5;
        } elseif ($vote->valor == -1) { // Voto negativo
            $points = -2;
        }

        // Aplicar puntos según la acción
        if ($action === 'created') {
            $author->increment('reputacion', $points);
        } elseif ($action === 'updated') {
            // Al actualizar un voto, primero revertir el efecto anterior
            $previousPoints = $vote->valor == 1 ? -2 : 5; // Opuesto al actual
            $author->increment('reputacion', $previousPoints + $points);
        } elseif ($action === 'deleted') {
            // Revertir los puntos cuando se elimina un voto
            $author->decrement('reputacion', $points);
        }

        // Asegurar que la reputación no sea negativa
        if ($author->reputacion < 0) {
            $author->update(['reputacion' => 0]);
        }
    }
}
