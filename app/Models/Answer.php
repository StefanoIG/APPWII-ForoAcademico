<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = ['contenido', 'contenido_markdown', 'contenido_html', 'question_id', 'user_id'];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    // Relación polimórfica para votos
    public function votes()
    {
        return $this->morphMany(Vote::class, 'votable');
    }
}
