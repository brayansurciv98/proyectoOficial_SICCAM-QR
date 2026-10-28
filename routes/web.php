<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\AsistenciaClaseController;

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

    // Máximo 5 intentos fallidos por minuto para el mismo usuario y equipo
    $llave = 'login:' . mb_strtolower($login) . '|' . $request->ip();
    if (RateLimiter::tooManyAttempts($llave, 5)) {
        return back()->withErrors([
            'email' => 'Demasiados intentos fallidos. Espere ' . RateLimiter::availableIn($llave) . ' segundos e intente de nuevo.',
        ])->onlyInput('email');
    }

    // 1) ADMIN (users)
    if (Auth::guard('web')->attempt(
        ['email' => $login, 'password' => $password],
        $remember
    )) {
        RateLimiter::clear($llave);
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
            RateLimiter::clear($llave);
            Auth::guard('docente')->login($docente, $remember);
            $request->session()->regenerate();
            $request->session()->put('tipo_usuario', 'docente');
            return redirect()->route('docente.dashboard');
        }
    }

    RateLimiter::hit($llave, 60);

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
    Route::get('/estudiantes/{estudiante}/tutor/credenciales', [EstudianteController::class, 'credencialesTutor'])
    ->name('estudiantes.tutor.credenciales');

    Route::get('/estudiantes/{estudiante}/tutor/credenciales/pdf', [EstudianteController::class, 'credencialesTutorPdf'])
        ->name('estudiantes.tutor.credenciales.pdf');

    // Docentes
    Route::resource('docentes', DocenteController::class);

    Route::get('/docentes/{docente}/credenciales', [DocenteController::class, 'credenciales'])
    ->name('docentes.credenciales');

    Route::get('/docentes/{docente}/credenciales/pdf', [DocenteController::class, 'credencialesPdf'])
    ->name('docentes.credenciales.pdf');

    // Exámenes
    // Sin parameters() Laravel nombra el parámetro {examene} y el destroy(Examen $examen) no lo enlaza
    Route::resource('examenes', ExamenController::class)
        ->only(['index', 'create', 'store', 'destroy'])
        ->parameters(['examenes' => 'examen']);

    // Asistencias
    Route::get('/asistencias/manual', [AsistenciaController::class, 'manual'])
        ->name('asistencias.manual');
    Route::post('/asistencias/manual', [AsistenciaController::class, 'storeManual'])
        ->name('asistencias.manual.store');
    Route::get('/asistencias', [AsistenciaController::class, 'index'])->name('asistencias.index');
    Route::get('/asistencias/{asistencia}', [AsistenciaController::class, 'show'])->name('asistencias.show');

    // Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])->name('reportes.pdf');
    Route::get('/reportes/excel', [ReporteController::class, 'excel'])->name('reportes.excel');

    // Comunicados
    Route::resource('comunicados', ComunicadoController::class)->only(['index', 'store', 'destroy']);

    // Configuración
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion', [ConfiguracionController::class, 'store'])->name('configuracion.store');

    Route::resource('horarios', HorarioController::class)
    ->only(['index', 'create', 'store', 'destroy']);
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

        // Estudiantes de sus cursos que hoy están como ausentes
        $ausentesHoy = collect();
        if ($docente->cursos->isNotEmpty()) {
            $ausentesHoy = \App\Models\Asistencia::with('estudiante')
                ->whereDate('fecha', today())
                ->where('estado', \App\Models\Asistencia::AUSENTE)
                ->whereHas('estudiante', function ($q) use ($docente) {
                    $q->where(function ($q) use ($docente) {
                        foreach ($docente->cursos as $c) {
                            $q->orWhere(fn ($q) => $q->where('curso', $c->curso)->where('paralelo', $c->paralelo));
                        }
                    });
                })
                ->get()
                ->sortBy(fn ($a) => $a->estudiante->curso . $a->estudiante->paralelo . $a->estudiante->apellidos);
        }

        // Clase en curso / próxima / sin clase
        $jornada = \App\Models\Horario::jornadaDe($docente);

        return view('docentes.dashboard', compact('docente', 'ausentesHoy', 'jornada'));
    })->name('dashboard');

        // Asistencia por clase (bloque del horario), solo hoy
    Route::get('/clases', [AsistenciaClaseController::class, 'index'])
        ->name('clases.index');
    Route::get('/clases/{horario}/asistencia', [AsistenciaClaseController::class, 'create'])
        ->name('clases.asistencia');
    Route::post('/clases/{horario}/asistencia', [AsistenciaClaseController::class, 'store'])
        ->name('clases.asistencia.store');


    // Asistencia manual del docente
    Route::get('/asistencias/manual', [AsistenciaController::class, 'manualDocente'])
        ->name('asistencias.manual');

    Route::post('/asistencias/manual', [AsistenciaController::class, 'storeManualDocente'])
        ->name('asistencias.manual.store');

    // Exámenes del docente
    Route::get('/examenes', [ExamenController::class, 'indexDocente'])
        ->name('examenes.index');
    
    Route::get('/examenes/create', [ExamenController::class, 'createDocente'])
        ->name('examenes.create');
    
    Route::post('/examenes', [ExamenController::class, 'storeDocente'])
        ->name('examenes.store');
    
    Route::delete('/examenes/{examen}', [ExamenController::class, 'destroyDocente'])
        ->name('examenes.destroy');
});