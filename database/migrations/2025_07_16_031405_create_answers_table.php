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
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('contenido');
            $table->integer('votos')->default(0);
            $table->timestamps();

            // Un usuario solo puede dar una respuesta por pregunta
            $table->unique(['question_id', 'user_id']);
        });

        // Ahora podemos definir la clave foránea en la tabla questions
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('mejor_respuesta_id')->references('id')->on('answers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Es importante eliminar la FK antes de borrar la tabla
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['mejor_respuesta_id']);
        });
        Schema::dropIfExists('answers');
    }
};
