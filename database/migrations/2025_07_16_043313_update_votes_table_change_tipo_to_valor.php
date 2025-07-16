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
        Schema::table('votes', function (Blueprint $table) {
            // Eliminar la columna tipo y agregar valor
            $table->dropColumn('tipo');
            $table->tinyInteger('valor')->after('votable_type'); // 1 para positivo, -1 para negativo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            // Revertir los cambios
            $table->dropColumn('valor');
            $table->enum('tipo', ['up', 'down'])->after('votable_type');
        });
    }
};
