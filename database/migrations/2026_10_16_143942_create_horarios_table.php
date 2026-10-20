<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('dia'); // 1=Lunes ... 5=Viernes
            $table->unsignedTinyInteger('bloque'); // 1,2,3,4
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedTinyInteger('curso'); // 1-6
            $table->enum('paralelo', ['A', 'B', 'C']);
            $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
            $table->foreignId('docente_id')->constrained('docentes')->restrictOnDelete();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();

            $table->unique(['dia', 'bloque', 'curso', 'paralelo'], 'horarios_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};