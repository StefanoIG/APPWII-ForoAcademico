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
        Schema::table('reports', function (Blueprint $table) {
            // Cambiar motivo a enum
            $table->dropColumn('motivo');
            $table->enum('motivo', ['spam', 'ofensivo', 'inapropiado', 'otro'])->after('reportable_type');
            
            // Agregar campos faltantes
            $table->text('descripcion')->nullable()->after('motivo');
            $table->text('observaciones')->nullable()->after('estado');
            $table->foreignId('revisado_por')->nullable()->constrained('users')->after('observaciones');
            $table->timestamp('revisado_en')->nullable()->after('revisado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['revisado_por']);
            $table->dropColumn(['descripcion', 'observaciones', 'revisado_por', 'revisado_en']);
            $table->dropColumn('motivo');
            $table->text('motivo')->after('reportable_type');
        });
    }
};
