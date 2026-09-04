<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ComunicadoController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DocenteController;
use App\Models\Docente;
use App\Http\Controllers\ExamenController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::guard('web')->check()) {
        return redirect()->route('dashboard');
    }
    if (Auth::guard('docente')->check()) {
        return redirect()->route('docente.dashboard');
    }
    return view('auth.login');
})->name('login');

Route::post('/', function (Request $request) {
    $data = $request->validate([
        'email'    => ['required', 'string'], // email o CI
        'password' => ['required'],
    ]);

    $login = trim($data['email']);
    $password = $data['password'];
    $remember = $request->boolean('remember');

    // 1) ADMIN (users)
    if (Auth::guard('web')->attempt(
        ['email' => $login, 'password' => $password],
        $remember
    )) {
        $request->session()->regenerate();
        $request->session()->put('tipo_usuario', 'admin');
        return redirect()->intended('/dashboard');
    }

    // 2) DOCENTE (docentes) por email o CI
    $docente = Docente::where(function ($q) use ($login) {
    $q->where('email', $login)->orWhere('ci', $login);
    })->first();

    if ($docente && $docente->estado === 'activo') {
        $ok = false;

        if (!empty($docente->password) && Hash::check($password, $docente->password)) {
            $ok = true;
        } elseif (!empty($docente->clave_inicial) && $password === $docente->clave_inicial) {
            $docente->password = Hash::make($password);
            $docente->save();
            $ok = true;
        }

        if ($ok) {
            Auth::guard('docente')->login($docente, $remember);
            $request->session()->regenerate();
            $request->session()->put('tipo_usuario', 'docente');
            return redirect()->route('docente.dashboard');
        }
    }

    return back()->withErrors([
        'email' => 'Las credenciales no coinciden con nuestros registros.',
    ])->onlyInput('email');
});

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    Auth::guard('docente')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

/*
|--------------------------------------------------------------------------
| RUTAS ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['tipo:admin'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Estudiantes
    Route::patch('/estudiantes/{estudiante}/toggle-estado', [EstudianteController::class, 'toggleEstado'])
        ->name('estudiantes.toggleEstado');
    Route::resource('estudiantes', EstudianteController::class);

    // Docentes
    Route::resource('docentes', DocenteController::class);

    // Exámenes
    Route::resource('examenes', ExamenController::class)
        ->only(['index', 'create', 'store', 'destroy']);

    // Asistencias
    Route::get('/asistencias/manual', [AsistenciaController::class, 'manual'])
        ->name('asistencias.manual');
    Route::post('/asistencias/manual', [AsistenciaController::class, 'storeManual'])
        ->name('asistencias.manual.store');
    Route::get('/asistencias', [AsistenciaController::class, 'index'])->name('asistencias.index');
    Route::get('/asistencias/{asistencia}', [AsistenciaController::class, 'show'])->name('asistencias.show');

    // Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

    // Comunicados
    Route::resource('comunicados', ComunicadoController::class)->only(['index', 'store', 'destroy']);

    // Configuración
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion', [ConfiguracionController::class, 'store'])->name('configuracion.store');
});

/*
|--------------------------------------------------------------------------
| RUTAS DOCENTE
|--------------------------------------------------------------------------
*/
Route::middleware(['tipo:docente'])->prefix('docente')->name('docente.')->group(function () {

    Route::get('/dashboard', function () {
        /** @var \App\Models\Docente|null $docente */
        $docente = Auth::guard('docente')->user();

        if (!$docente) {
            return redirect()->route('login');
        }

        $docente->load(['materia', 'cursos']);

        return view('docente.dashboard', compact('docente'));
    })->name('dashboard');

    // Aquí luego: asistencia manual y exámenes del docente
});