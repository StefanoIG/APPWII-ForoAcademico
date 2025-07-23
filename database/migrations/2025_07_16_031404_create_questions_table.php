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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('titulo');
            $table->text('contenido');
            $table->enum('estado', ['abierta', 'resuelta', 'cerrada'])->default('abierta');
            $table->integer('votos')->default(0);
            $table->unsignedBigInteger('mejor_respuesta_id')->nullable(); // Se añadirá la FK después de crear la tabla answers
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
