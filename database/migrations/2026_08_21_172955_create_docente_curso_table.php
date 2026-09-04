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
    Schema::create('docente_curso', function (Blueprint $table) {
        $table->id();
        $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
        $table->unsignedTinyInteger('curso');
        $table->enum('paralelo', ['A', 'B', 'C']);
        $table->timestamps();

        $table->unique(['docente_id', 'curso', 'paralelo']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('docente_curso');
    }
};
