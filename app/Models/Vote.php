<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'valor', 'votable_id', 'votable_type'];

    // Relación con el usuario que vota
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación polimórfica inversa
    public function votable()
    {
        return $this->morphTo();
    }
}
