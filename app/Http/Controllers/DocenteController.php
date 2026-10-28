<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\DocenteCurso;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocenteController extends Controller
{
    public function index()
    {
        $docentes = Docente::with(['materia', 'cursos'])
            ->orderBy('id', 'desc')
            ->get();

        return view('docentes.index', compact('docentes'));
    }

    public function create()
    {
        $materias = Materia::orderBy('nombre')->get();
        return view('docentes.create', compact('materias'));
    }

    public function store(Request $request)
    {
        $request->validate($this->reglas(), $this->mensajes());
        $this->validarCursos($request);

        $plainPassword = Str::random(8);

        $docente = Docente::create([
            'nombres'       => $this->capitalizar($request->nombres),
            'apellidos'     => $this->capitalizar($request->apellidos),
            'genero'        => $request->genero ?: null,
            'ci'            => $request->ci ? trim($request->ci) : null,
            'email'         => $request->email ? trim($request->email) : null,
            'telefono'      => $request->telefono ? trim($request->telefono) : null,
            'materia_id'    => $request->materia_id,
            'password'      => Hash::make($plainPassword),
            'clave_inicial' => $plainPassword,
            'estado'        => 'activo',
        ]);

        foreach ($request->cursos as $item) {
            DocenteCurso::create([
                'docente_id' => $docente->id,
                'curso'      => (int) $item['curso'],
                'paralelo'   => strtoupper($item['paralelo']),
            ]);
        }

        return redirect()->route('docentes.index')->with('credenciales_docente', [
            'nombre'   => $docente->nombres . ' ' . $docente->apellidos,
            'usuario'  => $docente->ci ?? $docente->email,
            'password' => $plainPassword,
            'materia'  => $docente->materia->nombre,
        ]);
    }

    public function show(Docente $docente)
    {
        $docente->load(['materia', 'cursos']);
        return view('docentes.show', compact('docente'));
    }

    public function edit(Docente $docente)
    {
        $materias = Materia::orderBy('nombre')->get();
        $docente->load('cursos');
    
        $cursosOld = old('cursos');
    
        if (!$cursosOld) {
            $cursosOld = $docente->cursos->map(function ($c) {
                return [
                    'curso' => (string) $c->curso,
                    'paralelo' => $c->paralelo,
                ];
            })->values()->all();
        }
    
        if (empty($cursosOld)) {
            $cursosOld = [];
        }
    
        return view('docentes.edit', compact('docente', 'materias', 'cursosOld'));
    }

    public function update(Request $request, Docente $docente)
    {
        $request->validate(
            $this->reglas($docente) + ['estado' => ['required', 'in:activo,inactivo']],
            $this->mensajes()
        );
        $this->validarCursos($request, $docente);

        $docente->update([
            'nombres'    => $this->capitalizar($request->nombres),
            'apellidos'  => $this->capitalizar($request->apellidos),
            'genero'     => $request->genero ?: null,
            'ci'         => $request->ci ? trim($request->ci) : null,
            'email'      => $request->email ? trim($request->email) : null,
            'telefono'   => $request->telefono ? trim($request->telefono) : null,
            'materia_id' => $request->materia_id,
            'estado'     => $request->estado,
        ]);

        // Reasignar cursos
        $docente->cursos()->delete();
        foreach ($request->cursos as $item) {
            DocenteCurso::create([
                'docente_id' => $docente->id,
                'curso'      => (int) $item['curso'],
                'paralelo'   => strtoupper($item['paralelo']),
            ]);
        }

        return redirect()->route('docentes.index')
            ->with('success', 'Docente actualizado correctamente.');
    }

    public function destroy(Docente $docente)
    {
        // horarios.docente_id tiene restrictOnDelete: borrar daría error 500
        if ($docente->horarios()->exists()) {
            return redirect()->route('docentes.index')
                ->withErrors(['docente' => 'No se puede eliminar: el docente tiene horarios asignados. Elimine o reasigne sus horarios primero.']);
        }

        $docente->delete();
        return redirect()->route('docentes.index')
            ->with('success', 'Docente eliminado correctamente.');
    }

    public function credenciales(Docente $docente)
    {
        $docente->load('materia');

        if (empty($docente->clave_inicial)) {
            return redirect()->route('docentes.index')
                ->with('success', 'Este docente no tiene clave inicial guardada.');
        }

        return redirect()->route('docentes.index')->with('ver_credenciales_docente', [
            'id'       => $docente->id,
            'nombre'   => $docente->nombres . ' ' . $docente->apellidos,
            'materia'  => $docente->materia->nombre ?? '—',
            'usuario'  => $docente->ci ?? $docente->email,
            'password' => $docente->clave_inicial,
        ]);
    }

    public function credencialesPdf(Docente $docente)
    {
        $docente->load('materia');

        if (empty($docente->clave_inicial)) {
            return redirect()->route('docentes.index')
                ->with('success', 'Este docente no tiene clave inicial guardada.');
        }

        $data = [
            'nombre'   => $docente->nombres . ' ' . $docente->apellidos,
            'materia'  => $docente->materia->nombre ?? '—',
            'usuario'  => $docente->ci ?? $docente->email,
            'password' => $docente->clave_inicial,
            'fecha'    => now()->format('d/m/Y H:i'),
        ];

        $nombreArchivo = 'Credenciales_Docente_' .
            str_replace(' ', '_', $docente->nombres . '_' . $docente->apellidos) .
            '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('docentes.credenciales_pdf', $data);

        return $pdf->download($nombreArchivo);
    }


    private function reglas(?Docente $docente = null): array
    {
        $id = $docente?->id;

        return [
            'nombres'    => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
            'apellidos'  => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
            'genero'     => ['nullable', 'in:M,F,Otro'],
            // Inicia sesión con su CI o su correo: al menos uno es obligatorio
            'ci'         => ['nullable', 'required_without:email', 'digits_between:5,10', Rule::unique('docentes', 'ci')->ignore($id)],
            'email'      => ['nullable', 'required_without:ci', 'email', 'max:150', Rule::unique('docentes', 'email')->ignore($id)],
            'telefono'   => ['nullable', 'digits_between:7,8'],
            'materia_id' => ['required', 'exists:materias,id'],
            'cursos'     => ['required', 'array', 'min:1'],
            'cursos.*.curso'    => ['required', 'integer', 'min:1', 'max:6'],
            'cursos.*.paralelo' => ['required', 'in:A,B,C'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombres.regex'          => 'Los nombres solo deben contener letras.',
            'apellidos.regex'        => 'Los apellidos solo deben contener letras.',
            'ci.required_without'    => 'Ingrese el CI o el correo: el docente los usa para iniciar sesión.',
            'email.required_without' => 'Ingrese el CI o el correo: el docente los usa para iniciar sesión.',
            'ci.digits_between'      => 'El CI debe tener entre 5 y 10 números.',
            'ci.unique'              => 'Ya existe un docente con ese CI.',
            'email.unique'           => 'Ya existe un docente con ese correo.',
            'telefono.digits_between' => 'El teléfono debe tener 7 u 8 números.',
            'cursos.required'        => 'Asigna al menos un curso.',
        ];
    }

    /**
     * Cursos sin repetir y coherentes con sus horarios: no se puede quitar un curso
     * ni cambiar la materia mientras tenga clases registradas en Horarios.
     */
    private function validarCursos(Request $request, ?Docente $docente = null): void
    {
        $elegidos = collect($request->cursos)
            ->map(fn ($c) => (int) $c['curso'] . '|' . strtoupper($c['paralelo']));

        $repetidos = $elegidos->duplicates()->unique();
        if ($repetidos->isNotEmpty()) {
            throw ValidationException::withMessages([
                'cursos' => 'Hay cursos repetidos: ' . $repetidos->map(fn ($c) => str_replace('|', '° ', $c))->implode(', ') . '.',
            ]);
        }

        if (!$docente) {
            return;
        }

        $horarios = $docente->horarios()->get();

        $otraMateria = $horarios->where('materia_id', '!=', (int) $request->materia_id)->count();
        if ($otraMateria > 0) {
            throw ValidationException::withMessages([
                'materia_id' => "No se puede cambiar la materia: el docente tiene {$otraMateria} clase(s) en Horarios con su materia actual. Elimínelas primero.",
            ]);
        }

        $quitados = $horarios
            ->map(fn ($h) => (int) $h->curso . '|' . $h->paralelo)
            ->unique()
            ->diff($elegidos);
        if ($quitados->isNotEmpty()) {
            throw ValidationException::withMessages([
                'cursos' => 'No se puede quitar ' . $quitados->map(fn ($c) => str_replace('|', '° ', $c))->implode(', ')
                    . ': el docente tiene clases con ese curso en Horarios. Elimínelas primero.',
            ]);
        }
    }

    private function capitalizar(?string $texto): ?string
    {
        if (!$texto) return $texto;
        $texto = trim(mb_strtolower($texto, 'UTF-8'));
        return collect(preg_split('/\s+/', $texto))
            ->filter()
            ->map(fn ($p) => mb_convert_case($p, MB_CASE_TITLE, 'UTF-8'))
            ->implode(' ');
    }
}