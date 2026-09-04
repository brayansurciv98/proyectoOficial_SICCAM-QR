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
    Schema::create('examenes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
        $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
        $table->unsignedTinyInteger('curso');
        $table->enum('paralelo', ['A', 'B', 'C']);
        $table->string('titulo');
        $table->date('fecha');
        $table->time('hora')->nullable();
        $table->text('observacion')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examenes');
    }
};
