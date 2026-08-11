<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EstudianteController;

Route::get('/', function () {
    return view('auth.login');
})->name('login');

Route::get('/dashboard', function () {
    return view('admin.dashboard');
})->name('dashboard');

// ==================== ESTUDIANTES ====================
Route::get('/estudiantes', [EstudianteController::class, 'index'])->name('estudiantes');
Route::get('/estudiantes/create', [EstudianteController::class, 'create'])->name('estudiantes.create');
Route::post('/estudiantes', [EstudianteController::class, 'store'])->name('estudiantes.store');

// =====================================================

Route::get('/asistencias', function () {
    return view('admin.asistencias');
})->name('asistencias');

Route::get('/reportes', function () {
    return view('admin.reportes');
})->name('reportes');

Route::get('/comunicados', function () {
    return view('admin.comunicados');
})->name('comunicados');

Route::get('/configuracion', function () {
    return view('admin.configuracion');
})->name('configuracion');

Route::get('/estudiantes/{estudiante}', [EstudianteController::class, 'show'])->name('estudiantes.show');
Route::get('/estudiantes/{estudiante}/edit', [EstudianteController::class, 'edit'])->name('estudiantes.edit');
Route::put('/estudiantes/{estudiante}', [EstudianteController::class, 'update'])->name('estudiantes.update');
Route::delete('/estudiantes/{estudiante}', [EstudianteController::class, 'destroy'])->name('estudiantes.destroy');
Route::patch('/estudiantes/{estudiante}/toggle', [EstudianteController::class, 'toggleEstado'])->name('estudiantes.toggle');