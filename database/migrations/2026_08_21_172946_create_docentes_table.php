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
    Schema::create('docentes', function (Blueprint $table) {
        $table->id();
        $table->string('nombres', 100);
        $table->string('apellidos', 100);
        $table->string('ci', 20)->nullable()->unique();
        $table->string('email')->nullable()->unique();
        $table->string('telefono', 20)->nullable();
        $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
        $table->string('password');
        $table->string('clave_inicial', 20)->nullable();
        $table->enum('estado', ['activo', 'inactivo'])->default('activo');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};
