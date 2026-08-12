<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function index()
    {
        // Carga los valores como un array clave => valor
        $config = Configuracion::pluck('valor', 'clave')->toArray();

        return view('configuracion.index', compact('config'));
    }

    public function store(Request $request)
    {
        $datos = $request->except(['_token', 'tab_active']);

        foreach ($datos as $clave => $valor) {
            Configuracion::updateOrCreate(
                ['clave' => $clave],
                [
                    'valor' => $valor,
                    'grupo' => $this->determinarGrupo($clave),
                ]
            );
        }

        return redirect()
            ->route('configuracion.index', ['tab' => $request->input('tab_active', 'institucion')])
            ->with('success', 'Configuración actualizada correctamente.');
    }

    private function determinarGrupo(string $clave): string
    {
        if (in_array($clave, ['hora_ingreso', 'hora_limite_atraso', 'hora_cierre_registro'])) {
            return 'horarios';
        }
        if (in_array($clave, ['tolerancia_minutos', 'notificar_ausencia', 'canal_notificacion'])) {
            return 'parametros';
        }
        return 'institucion';
    }
}