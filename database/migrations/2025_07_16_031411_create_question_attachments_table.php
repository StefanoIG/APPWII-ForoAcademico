<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->string('original_name'); // Nombre original del archivo
            $table->string('file_name'); // Nombre único generado
            $table->string('file_path'); // Ruta del archivo en storage
            $table->string('mime_type'); // Tipo MIME del archivo
            $table->unsignedBigInteger('file_size'); // Tamaño en bytes
            $table->enum('file_type', ['image', 'document', 'video', 'audio', 'other'])->default('other');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_attachments');
    }
};
