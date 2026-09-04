<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckTipoUsuario
{
    public function handle(Request $request, Closure $next, string $tipo)
    {
        if ($tipo === 'admin' && Auth::guard('web')->check()) {
            return $next($request);
        }

        if ($tipo === 'docente' && Auth::guard('docente')->check()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        Auth::guard('docente')->logout();

        return redirect('/')->withErrors([
            'email' => 'No tienes permiso para acceder a esta sección.',
        ]);
    }
}