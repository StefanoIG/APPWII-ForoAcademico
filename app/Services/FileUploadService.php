<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\QuestionAttachment;

class FileUploadService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_EXTENSIONS = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
        'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'aac'],
    ];

    /**
     * Subir archivo y crear registro en base de datos
     */
    public function uploadFile(UploadedFile $file, int $questionId): QuestionAttachment
    {
        $this->validateFile($file);

        $fileType = $this->getFileType($file);
        $fileName = $this->generateFileName($file);
        $filePath = $this->getFilePath($fileType, $fileName);

        // Procesar imagen si es necesario
        if ($fileType === 'image' && $this->shouldOptimizeImage($file)) {
            $this->processImage($file, $filePath);
        } else {
            Storage::disk('public')->put($filePath, file_get_contents($file));
        }

        // Crear registro en base de datos
        return QuestionAttachment::create([
            'question_id' => $questionId,
            'original_name' => $file->getClientOriginalName(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_type' => $fileType,
        ]);
    }

    /**
     * Validar archivo
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('El archivo no es válido.');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('El archivo es demasiado grande. Máximo 10MB.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = collect(self::ALLOWED_EXTENSIONS)->flatten();

        if (!$allowedExtensions->contains($extension)) {
            throw new \InvalidArgumentException('Tipo de archivo no permitido.');
        }
    }

    /**
     * Determinar el tipo de archivo
     */
    private function getFileType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        foreach (self::ALLOWED_EXTENSIONS as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }
        
        return 'other';
    }

    /**
     * Generar nombre único para el archivo
     */
    private function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    /**
     * Obtener ruta del archivo según el tipo
     */
    private function getFilePath(string $fileType, string $fileName): string
    {
        return "uploads/{$fileType}s/{$fileName}";
    }

    /**
     * Verificar si debe optimizar la imagen
     */
    private function shouldOptimizeImage(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, ['jpg', 'jpeg', 'png']);
    }

    /**
     * Procesar y optimizar imagen
     */
    private function processImage(UploadedFile $file, string $filePath): void
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
            
            // Redimensionar si es muy grande
            if ($image->width() > 1920 || $image->height() > 1080) {
                $image->scale(width: 1920, height: 1080);
            }
            
            // Optimizar calidad
            $fullPath = Storage::disk('public')->path($filePath);
            $directory = dirname($fullPath);
            
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $image->save($fullPath, quality: 85);
        } catch (\Exception $e) {
            // Si falla el procesamiento de imagen, guardar como archivo normal
            Log::warning('Image processing failed, saving as regular file: ' . $e->getMessage());
            Storage::disk('public')->put($filePath, file_get_contents($file));
        }
    }

    /**
     * Eliminar archivo
     */
    public function deleteFile(QuestionAttachment $attachment): bool
    {
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }
        
        return $attachment->delete();
    }

    /**
     * Obtener información de archivos permitidos
     */
    public static function getAllowedFileInfo(): array
    {
        return [
            'max_size' => self::MAX_FILE_SIZE,
            'max_size_formatted' => number_format(self::MAX_FILE_SIZE / (1024 * 1024), 0) . 'MB',
            'allowed_extensions' => self::ALLOWED_EXTENSIONS,
        ];
    }
}
