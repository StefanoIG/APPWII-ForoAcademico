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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('reputacion')->default(0);
            $table->enum('rol', ['admin', 'moderador', 'usuario'])->default('usuario');
            $table->enum('estado', ['activo', 'bloqueado'])->default('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reputacion', 'rol', 'estado']);
        });
    }
};
