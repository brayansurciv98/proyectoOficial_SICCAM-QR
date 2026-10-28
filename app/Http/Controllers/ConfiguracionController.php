<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConfiguracionController extends Controller
{
    /**
     * Campos de cada pestaña con sus reglas. Solo se guardan estas claves.
     */
    private const REGLAS = [
        'institucion' => [
            'colegio_nombre'    => ['required', 'string', 'max:150'],
            'codigo_sie'        => ['nullable', 'digits_between:6,10'],
            'colegio_direccion' => ['nullable', 'string', 'max:200'],
            'colegio_telefono'  => ['nullable', 'regex:/^[0-9][0-9\- ]{5,14}$/'],
            'colegio_email'     => ['nullable', 'email', 'max:150'],
            'colegio_director'  => ['nullable', 'string', 'max:150', 'regex:/^[\p{L}\s.]+$/u'],
        ],
        'horarios' => [
            'hora_ingreso'          => ['required', 'date_format:H:i'],
            'hora_marcar_ausencias' => ['required', 'date_format:H:i'],
        ],
        'parametros' => [
            'tolerancia_minutos' => ['required', 'integer', 'min:0', 'max:60'],
            'notificar_ausencia' => ['required', 'in:si,no'],
        ],
    ];

    private const MENSAJES = [
        'colegio_nombre.required'          => 'El nombre de la unidad educativa es obligatorio.',
        'codigo_sie.digits_between'        => 'El código SIE debe tener entre 6 y 10 números.',
        'colegio_telefono.regex'           => 'El teléfono solo puede tener números, guiones y espacios (ej. 4-1234567).',
        'colegio_email.email'              => 'El correo institucional no es válido.',
        'colegio_director.regex'           => 'El nombre del director solo debe contener letras (puede incluir "Lic." o "Prof.").',
        'hora_ingreso.date_format'         => 'La hora de ingreso no es válida.',
        'hora_marcar_ausencias.date_format' => 'La hora para marcar ausencias no es válida.',
        'tolerancia_minutos.min'           => 'La tolerancia no puede ser negativa.',
        'tolerancia_minutos.max'           => 'La tolerancia no puede ser mayor a 60 minutos.',
    ];

    public function index()
    {
        // Carga los valores como un array clave => valor
        $config = Configuracion::pluck('valor', 'clave')->toArray();

        // Presente hasta (ingreso + tolerancia) y hora de las ausencias, como los usa el sistema
        $presenteHasta = Asistencia::horaLimite(now())->format('H:i');
        $horaAusencias = Asistencia::horaAusencias(now())->format('H:i');

        return view('configuracion.index', compact('config', 'presenteHasta', 'horaAusencias'));
    }

    public function store(Request $request)
    {
        $tab = $request->input('tab_active');
        if (!array_key_exists($tab, self::REGLAS)) {
            $tab = 'institucion';
        }

        $datos = $request->validate(self::REGLAS[$tab], self::MENSAJES);

        // La hora de las ausencias debe ser posterior a "presente hasta" (ingreso + tolerancia)
        if ($tab !== 'institucion') {
            $ingreso = $datos['hora_ingreso'] ?? Configuracion::valor('hora_ingreso', Asistencia::HORA_INGRESO_DEFECTO);
            $tolerancia = (int) ($datos['tolerancia_minutos'] ?? Configuracion::valor('tolerancia_minutos', Asistencia::TOLERANCIA_DEFECTO));
            $ausencias = $datos['hora_marcar_ausencias'] ?? Configuracion::valor('hora_marcar_ausencias');

            $presenteHasta = now()->setTimeFromTimeString($ingreso)->addMinutes($tolerancia);
            if ($ausencias && now()->setTimeFromTimeString($ausencias)->lte($presenteHasta)) {
                throw ValidationException::withMessages([
                    $tab === 'horarios' ? 'hora_marcar_ausencias' : 'tolerancia_minutos' =>
                        'La hora para marcar ausencias (' . substr($ausencias, 0, 5) . ') debe ser posterior a la hora '
                        . 'hasta la que se considera presente (' . $presenteHasta->format('H:i') . ').',
                ])->redirectTo(route('configuracion.index', ['tab' => $tab]));
            }
        }

        foreach ($datos as $clave => $valor) {
            Configuracion::updateOrCreate(
                ['clave' => $clave],
                ['valor' => $valor ?? '', 'grupo' => $tab]
            );
        }

        return redirect()
            ->route('configuracion.index', ['tab' => $tab])
            ->with('success', 'Configuración actualizada correctamente.');
    }
}
