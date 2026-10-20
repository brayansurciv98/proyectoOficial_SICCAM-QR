<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asistencia que toma el docente en cada clase (bloque del horario).
     * Es aparte de "asistencias", que es el ingreso al colegio (QR).
     */
    public function up(): void
    {
        Schema::create('asistencias_clase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_id')->constrained('horarios')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('estado', ['presente', 'tardanza', 'ausente']);
            $table->string('observacion')->nullable();
            $table->timestamps();

            // Un registro por estudiante, clase y día
            $table->unique(['horario_id', 'estudiante_id', 'fecha'], 'asistencia_clase_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_clase');
    }
};