<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutores', function (Blueprint $table) {
            // Identificador del celular del tutor en Firebase
            $table->string('fcm_token', 512)->nullable()->after('clave_inicial');
        });

        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('tutores')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes')->cascadeOnDelete();
            $table->enum('tipo', ['ausencia', 'tardanza', 'comunicado']);
            $table->string('titulo');
            $table->text('mensaje');
            $table->date('fecha');
            $table->boolean('enviada_push')->default(false);
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();

            // Un solo aviso por tutor, estudiante, tipo y día
            $table->unique(['tutor_id', 'estudiante_id', 'tipo', 'fecha'], 'notificaciones_unica_por_dia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');

        Schema::table('tutores', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};