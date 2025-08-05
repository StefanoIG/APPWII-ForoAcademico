<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'file_type'
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Relación con Question
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Obtener la URL del archivo
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Obtener el tipo de archivo (alias para file_type)
     */
    public function getTipoAttribute(): string
    {
        return $this->file_type;
    }

    /**
     * Verificar si es una imagen
     */
    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    /**
     * Obtener el tamaño formateado
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
