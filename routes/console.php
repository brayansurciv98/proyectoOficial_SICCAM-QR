<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Cache;
use App\Models\Asistencia;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cierre del ingreso: el comando revisa cada minuto y marca las ausencias una vez al día,
// a la hora de Configuración → "Marcar ausencias a las" (por defecto 13:50)
Schedule::command('asistencias:cerrar-ingreso')
    ->weekdays()
    ->everyMinute()
    ->when(fn () => now()->gte(Asistencia::horaAusencias(now()))
        && !Cache::has('ausencias-marcadas:' . now()->toDateString()))
    ->withoutOverlapping();