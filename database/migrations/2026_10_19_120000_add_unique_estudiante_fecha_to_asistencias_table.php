<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un solo registro de asistencia por estudiante y día
     * (evita duplicados entre Raspberry, sincronización offline y registro manual).
     */
    public function up(): void
    {
        $duplicados = DB::table('asistencias')
            ->select('estudiante_id', 'fecha', DB::raw('COUNT(*) as total'))
            ->groupBy('estudiante_id', 'fecha')
            ->having('total', '>', 1)
            ->get();

        if ($duplicados->isNotEmpty()) {
            $lista = $duplicados
                ->map(fn ($d) => "estudiante_id={$d->estudiante_id} fecha={$d->fecha} ({$d->total} registros)")
                ->implode(PHP_EOL);

            throw new RuntimeException(
                'Hay asistencias duplicadas. Deje un solo registro por estudiante y día y vuelva a migrar:' . PHP_EOL . $lista
            );
        }

        Schema::table('asistencias', function (Blueprint $table) {
            // Primero el único, así la FK de estudiante_id siempre tiene un índice que usar
            $table->unique(['estudiante_id', 'fecha'], 'asistencias_estudiante_fecha_unique');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex(['estudiante_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->index(['estudiante_id', 'fecha']);
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropUnique('asistencias_estudiante_fecha_unique');
        });
    }
};
