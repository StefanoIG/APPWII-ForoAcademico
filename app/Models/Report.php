<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'reportable_id', 
        'reportable_type', 
        'motivo', 
        'descripcion', 
        'estado',
        'observaciones',
        'revisado_por',
        'revisado_en'
    ];

    protected $casts = [
        'revisado_en' => 'datetime',
    ];

    // Relación con el usuario que reporta
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con el usuario que revisa el reporte
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    // Relación polimórfica con el contenido reportado
    public function reportable()
    {
        return $this->morphTo();
    }
}
