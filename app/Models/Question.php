<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'contenido', 'category_id', 'user_id'];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function bestAnswer()
    {
        return $this->belongsTo(Answer::class, 'mejor_respuesta_id');
    }

    // Relación polimórfica para votos
    public function votes()
    {
        return $this->morphMany(Vote::class, 'votable');
    }
}
