<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cierre del ingreso: 5 min después de la hora límite (13:30 + 15 de tolerancia)
Schedule::command('asistencias:cerrar-ingreso')
    ->weekdays()
    ->at('13:50');