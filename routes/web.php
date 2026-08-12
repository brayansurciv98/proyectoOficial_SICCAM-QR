<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ComunicadoController;
use App\Http\Controllers\ConfiguracionController;   

// Autenticación
Route::get('/', function () {
    return view('auth.login');
})->name('login');

// Panel Principal (Dashboard con datos reales de BD)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ==================== MÓDULO ESTUDIANTES ====================
// Genera automáticamente: index, create, store, show, edit, update, destroy
Route::resource('estudiantes', EstudianteController::class);

// Ruta personalizada para cambiar estado (Activo/Inactivo)
Route::patch('/estudiantes/{estudiante}/toggle', [EstudianteController::class, 'toggleEstado'])->name('estudiantes.toggle');

// ==================== OTROS MÓDULOS ====================
// ==================== MÓDULO ASISTENCIAS ====================
Route::get('/asistencias', [AsistenciaController::class, 'index'])->name('asistencias.index');
Route::get('/asistencias/{asistencia}', [AsistenciaController::class, 'show'])->name('asistencias.show');

// ==================== MÓDULO REPORTES ====================
Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

// ==================== MÓDULO COMUNICADOS ====================
Route::resource('comunicados', ComunicadoController::class)->only(['index', 'store', 'destroy']);

// ==================== MÓDULO CONFIGURACIÓN ====================
Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
Route::post('configuracion', [ConfiguracionController::class, 'store'])->name('configuracion.store');

// 1. Ruta personalizada para cambiar de estado (PATCH)
Route::patch('estudiantes/{estudiante}/toggle-estado', [EstudianteController::class, 'toggleEstado'])
    ->name('estudiantes.toggleEstado');

// 2. Rutas Resource habituales para estudiantes
Route::resource('estudiantes', EstudianteController::class);