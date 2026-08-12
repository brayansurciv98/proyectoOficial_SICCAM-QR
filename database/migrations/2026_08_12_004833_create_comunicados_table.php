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
        Schema::create('comunicados', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('mensaje');
            $table->enum('destinatario', ['todos', 'curso', 'tutor'])->default('todos');
            $table->string('curso_destino')->nullable();    // Ej: '4'
            $table->string('paralelo_destino')->nullable(); // Ej: 'B'
            $table->foreignId('tutor_id')->nullable()->constrained('tutores')->onDelete('cascade');
            $table->enum('canal', ['whatsapp', 'email', 'sistema'])->default('sistema');
            $table->enum('estado', ['enviado', 'pendiente', 'fallido'])->default('enviado');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comunicados');
    }
};