<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Género del docente (opcional), como el del estudiante: define su ícono en el panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            $table->enum('genero', ['M', 'F', 'Otro'])->nullable()->after('apellidos');
        });
    }

    public function down(): void
    {
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropColumn('genero');
        });
    }
};
