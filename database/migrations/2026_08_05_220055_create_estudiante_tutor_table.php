<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiante_tutor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            $table->foreignId('tutor_id')->constrained('tutores')->onDelete('cascade');
            $table->string('parentesco', 50)->nullable();
            $table->boolean('es_principal')->default(true);
            $table->timestamps();

            $table->unique(['estudiante_id', 'tutor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_tutor');
    }
};