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
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique(); // Ej: 'hora_entrada', 'tolerancia_minutos', 'nombre_colegio'
            $table->text('valor')->nullable();  // Ej: '08:00', '15', 'René Barrientos Ortuño'
            $table->string('grupo')->default('general'); // 'horarios', 'institucion', 'notificaciones'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};