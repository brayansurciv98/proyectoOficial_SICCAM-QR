<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TutorApiController;
use App\Http\Controllers\Api\AsistenciaApiController;

// App tutores (máximo 10 intentos de login por minuto)
Route::post('/tutor/login', [TutorApiController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tutor/hijos', [TutorApiController::class, 'getHijos']);
    Route::get('/tutor/hijos/{id}/asistencias', [TutorApiController::class, 'getAsistenciasHijo']);
    Route::post('/tutor/fcm-token', [TutorApiController::class, 'guardarFcmToken']);
    Route::get('/tutor/notificaciones', [TutorApiController::class, 'getNotificaciones']);
    Route::post('/tutor/notificaciones/leidas', [TutorApiController::class, 'marcarLeidas']);
    Route::post('/tutor/logout', [TutorApiController::class, 'logout']);
    Route::get('/tutor/examenes', [TutorApiController::class, 'getExamenes']);
});

// Raspberry Pi (sin login web, sin CSRF)
Route::post('/asistencias/marcar', [AsistenciaApiController::class, 'marcarAsistencia']);