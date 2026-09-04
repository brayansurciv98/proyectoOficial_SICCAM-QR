<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\DocenteCurso;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        $request->validate([
            'nombres'    => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
            'apellidos'  => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
            'ci'         => ['nullable', 'regex:/^[0-9]+$/', 'max:20', 'unique:docentes,ci'],
            'email'      => ['nullable', 'email', 'max:150', 'unique:docentes,email'],
            'telefono'   => ['nullable', 'regex:/^[0-9]+$/', 'max:20'],
            'materia_id' => ['required', 'exists:materias,id'],
            'cursos'     => ['required', 'array', 'min:1'],
            'cursos.*.curso'    => ['required', 'integer', 'min:1', 'max:6'],
            'cursos.*.paralelo' => ['required', 'in:A,B,C'],
        ], [
            'nombres.regex'   => 'Los nombres solo deben contener letras.',
            'apellidos.regex' => 'Los apellidos solo deben contener letras.',
            'ci.regex'        => 'El CI solo debe contener números.',
            'telefono.regex'  => 'El teléfono solo debe contener números.',
            'cursos.required' => 'Asigna al menos un curso.',
        ]);

        $plainPassword = Str::random(8);

        $docente = Docente::create([
            'nombres'       => $this->capitalizar($request->nombres),
            'apellidos'     => $this->capitalizar($request->apellidos),
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
        return view('docentes.edit', compact('docente', 'materias'));
    }

    public function update(Request $request, Docente $docente)
    {
        $request->validate([
            'nombres'    => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
            'apellidos'  => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
            'ci'         => ['nullable', 'regex:/^[0-9]+$/', 'max:20', 'unique:docentes,ci,' . $docente->id],
            'email'      => ['nullable', 'email', 'max:150', 'unique:docentes,email,' . $docente->id],
            'telefono'   => ['nullable', 'regex:/^[0-9]+$/', 'max:20'],
            'materia_id' => ['required', 'exists:materias,id'],
            'estado'     => ['required', 'in:activo,inactivo'],
            'cursos'     => ['required', 'array', 'min:1'],
            'cursos.*.curso'    => ['required', 'integer', 'min:1', 'max:6'],
            'cursos.*.paralelo' => ['required', 'in:A,B,C'],
        ]);

        $docente->update([
            'nombres'    => $this->capitalizar($request->nombres),
            'apellidos'  => $this->capitalizar($request->apellidos),
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
        $docente->delete();
        return redirect()->route('docentes.index')
            ->with('success', 'Docente eliminado correctamente.');
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