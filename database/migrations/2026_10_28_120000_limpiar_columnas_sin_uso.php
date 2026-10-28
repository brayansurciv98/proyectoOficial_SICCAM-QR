<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Limpieza antes del diagrama entidad-relación:
 * - quita columnas y tablas que el sistema nunca usó;
 * - curso y paralelo del estudiante con el mismo tipo que en horarios, exámenes y docente_curso;
 * - los comunicados se envían como aviso en la app y notificación push (sin WhatsApp ni correo).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Estudiantes: sin foto (no hay carga de fotos); curso 1–6 y paralelo A/B/C obligatorios
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->unsignedTinyInteger('curso')->change();
            $table->enum('paralelo', ['A', 'B', 'C'])->change();
        });

        // Comunicados: a todos los tutores o a un curso; el canal siempre es la app
        Schema::table('comunicados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tutor_id');
            $table->dropColumn(['paralelo_destino', 'canal']);
        });
        Schema::table('comunicados', function (Blueprint $table) {
            $table->enum('destinatario', ['todos', 'curso'])->default('todos')->change();
            $table->unsignedTinyInteger('curso_destino')->nullable()->change();
            $table->enum('estado', ['enviado', 'pendiente'])->default('enviado')->change();
        });

        // Asistencia por clase: la observación nunca se pidió ni se mostró
        Schema::table('asistencias_clase', function (Blueprint $table) {
            $table->dropColumn('observacion');
        });

        // Opciones de Configuración que se quitaron (reemplazadas por "Marcar ausencias a las")
        DB::table('configuraciones')
            ->whereIn('clave', ['hora_limite_atraso', 'hora_cierre_registro', 'canal_notificacion'])
            ->delete();

        // Tablas de Laravel para funciones que el sistema no usa (recuperar contraseña por correo, lotes de trabajos)
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('job_batches');
    }

    public function down(): void
    {
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('asistencias_clase', function (Blueprint $table) {
            $table->string('observacion')->nullable()->after('estado');
        });

        Schema::table('comunicados', function (Blueprint $table) {
            $table->enum('destinatario', ['todos', 'curso', 'tutor'])->default('todos')->change();
            $table->string('curso_destino')->nullable()->change();
            $table->enum('estado', ['enviado', 'pendiente', 'fallido'])->default('enviado')->change();
        });
        Schema::table('comunicados', function (Blueprint $table) {
            $table->string('paralelo_destino')->nullable()->after('curso_destino');
            $table->foreignId('tutor_id')->nullable()->after('paralelo_destino')->constrained('tutores')->onDelete('cascade');
            $table->enum('canal', ['whatsapp', 'email', 'sistema'])->default('sistema')->after('tutor_id');
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('curso', 50)->nullable()->change();
            $table->string('paralelo', 10)->nullable()->change();
        });
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('paralelo');
        });
    }
};
