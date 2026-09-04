<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TutorApiController;
use App\Http\Controllers\Api\AsistenciaApiController;

// App tutores
Route::post('/tutor/login', [TutorApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tutor/hijos', [TutorApiController::class, 'getHijos']);
    Route::get('/tutor/hijos/{id}/asistencias', [TutorApiController::class, 'getAsistenciasHijo']);
});

// Raspberry Pi (sin login web, sin CSRF)
Route::post('/asistencias/marcar', [AsistenciaApiController::class, 'marcarAsistencia']);