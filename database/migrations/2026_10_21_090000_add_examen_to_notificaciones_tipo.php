<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Nuevo tipo de aviso: examen programado
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->enum('tipo', ['ausencia', 'tardanza', 'comunicado', 'examen'])->change();
        });
    }

    public function down(): void
    {
        DB::table('notificaciones')->where('tipo', 'examen')->delete();

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->enum('tipo', ['ausencia', 'tardanza', 'comunicado'])->change();
        });
    }
};