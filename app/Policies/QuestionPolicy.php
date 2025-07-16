<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    // Solo el autor de la pregunta puede actualizarla (y marcar la mejor respuesta)
    public function update(User $user, Question $question): bool
    {
        return $user->id === $question->user_id;
    }
}
