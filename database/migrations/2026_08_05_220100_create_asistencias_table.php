<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora_ingreso')->nullable();
            $table->enum('estado', ['presente', 'tardanza', 'ausente']);
            $table->text('observacion')->nullable();
            $table->string('registrado_por', 50)->default('sistema_qr');
            $table->timestamps();

            $table->index('fecha');
            $table->index(['estudiante_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};