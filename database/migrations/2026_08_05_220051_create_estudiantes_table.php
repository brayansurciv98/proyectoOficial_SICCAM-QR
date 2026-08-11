<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_estudiante', 30)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('ci', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['M', 'F', 'Otro'])->nullable();
            $table->string('curso', 50)->nullable();
            $table->string('paralelo', 10)->nullable();
            $table->string('foto')->nullable();
            $table->text('qr_data');
            $table->string('qr_imagen')->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'retirado'])->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};