<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class EstudianteController extends Controller
{
    // Listar estudiantes (READ)
    public function index()
    {
        $estudiantes = Estudiante::with('tutores')
            ->orderBy('id', 'desc')
            ->get();

        return view('estudiantes.index', compact('estudiantes'));
    }

    // Mostrar formulario de registro (CREATE)
    public function create()
    {
        return view('estudiantes.create');
    }

    // Guardar estudiante + tutor + QR (STORE)
    public function store(Request $request)
{
    $request->validate([
    // Tutor
    'tutor_nombres'   => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
    'tutor_apellidos' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
    'tutor_ci'        => ['nullable', 'regex:/^[0-9]+$/', 'max:20'],
    'tutor_telefono'  => ['nullable', 'regex:/^[0-9]+$/', 'max:20'],
    'tutor_email'     => ['nullable', 'email', 'max:150'],

    // Estudiante
    'nombres'          => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
    'apellidos'        => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
    'ci'               => ['nullable', 'regex:/^[0-9]+$/', 'max:20'],
    'fecha_nacimiento' => ['nullable', 'date'],
    'genero'           => ['nullable', 'in:M,F,Otro'],
    'curso'            => ['required', 'integer', 'min:1', 'max:6'],
    'paralelo'         => ['nullable', 'in:A,B,C'],
    'parentesco'       => ['nullable', 'string', 'max:50'],
], [
    'tutor_nombres.regex'   => 'Los nombres del tutor solo deben contener letras.',
    'tutor_apellidos.regex' => 'Los apellidos del tutor solo deben contener letras.',
    'tutor_ci.regex'        => 'El CI del tutor solo debe contener números.',
    'tutor_telefono.regex'  => 'El teléfono solo debe contener números.',
    'nombres.regex'         => 'Los nombres del estudiante solo deben contener letras.',
    'apellidos.regex'       => 'Los apellidos del estudiante solo deben contener letras.',
    'ci.regex'              => 'El CI del estudiante solo debe contener números.',
    'curso.required'        => 'El curso es obligatorio.',
    'curso.integer'         => 'El curso debe ser un número entre 1 y 6.',
    'curso.min'             => 'El curso debe ser entre 1 y 6.',
    'curso.max'             => 'El curso debe ser entre 1 y 6.',
    'paralelo.regex'        => 'El paralelo solo debe contener letras (ej: A, B, C).',
]);

    // Normalizar textos
    $tutorNombres   = $this->capitalizarNombre($request->tutor_nombres);
    $tutorApellidos = $this->capitalizarNombre($request->tutor_apellidos);
    $nombres        = $this->capitalizarNombre($request->nombres);
    $apellidos      = $this->capitalizarNombre($request->apellidos);
    $paralelo       = $request->paralelo ? mb_strtoupper(trim($request->paralelo), 'UTF-8') : null;
    $curso          = trim($request->curso);

    $plainPassword = null;
    $tutorCi       = $request->filled('tutor_ci') ? trim($request->tutor_ci) : null;
    $tutorEmail    = $request->filled('tutor_email') ? trim($request->tutor_email) : null;

    // Buscar tutor por CI o email
    $tutor = null;
    if ($tutorCi || $tutorEmail) {
        $tutor = Tutor::where(function ($query) use ($tutorCi, $tutorEmail) {
            if ($tutorCi) {
                $query->where('ci', $tutorCi);
            }
            if ($tutorEmail) {
                $query->orWhere('email', $tutorEmail);
            }
        })->first();
    }

    // Crear tutor si no existe
    if (!$tutor) {
        $plainPassword = Str::random(8);

        $tutor = Tutor::create([
            'nombres'       => $tutorNombres,
            'apellidos'     => $tutorApellidos,
            'ci'            => $tutorCi,
            'telefono'      => $request->tutor_telefono ? trim($request->tutor_telefono) : null,
            'email'         => $tutorEmail,
            'password'      => Hash::make($plainPassword),
            'clave_inicial' => $plainPassword,
            'estado'        => 'activo',
        ]);
    }

    // Código único
    $ultimoId = Estudiante::max('id') ?? 0;
    $codigo   = 'EST-' . date('Y') . '-' . str_pad($ultimoId + 1, 4, '0', STR_PAD_LEFT);

    // QR
    $qrData = $codigo . '|' . $nombres . ' ' . $apellidos . '|' . $curso;

    // Crear estudiante
    $estudiante = Estudiante::create([
        'codigo_estudiante' => $codigo,
        'nombres'           => $nombres,
        'apellidos'         => $apellidos,
        'ci'                => $request->ci ? trim($request->ci) : null,
        'fecha_nacimiento'  => $request->fecha_nacimiento,
        'genero'            => $request->genero,
        'curso'             => (int) $request->curso,
        'paralelo'          => $paralelo,
        'qr_data'           => $qrData,
        'estado'            => 'activo',
    ]);

    // Relación tutor
    $estudiante->tutores()->attach($tutor->id, [
        'parentesco'   => $request->parentesco ?? 'Tutor',
        'es_principal' => true,
    ]);

    // Imagen QR
    $nombreArchivo = 'qr/' . $codigo . '.png';

    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($qrData)
        ->size(300)
        ->margin(10)
        ->build();

    Storage::disk('public')->put($nombreArchivo, $result->getString());

    $estudiante->update([
        'qr_imagen' => $nombreArchivo,
    ]);

    return redirect()->route('estudiantes.index')->with('credenciales_tutor', [
        'tutor_nombre' => $tutor->nombres . ' ' . $tutor->apellidos,
        'usuario_ci'   => $tutor->ci ?? $tutor->email,
        'password'     => $plainPassword ?? ($tutor->clave_inicial ?? 'Tutor registrado anteriormente'),
        'estudiante'   => $estudiante->nombres . ' ' . $estudiante->apellidos,
        'codigo_est'   => $codigo,
    ]);
}

    // Ver detalle (SHOW)
    public function show(Estudiante $estudiante)
    {
        $estudiante->load(['tutores', 'asistencias' => function($q) {
            $q->latest()->take(5);
        }]);

        $totalPresentes = $estudiante->asistencias()->where('estado', 'presente')->count();
        $totalTardanzas = $estudiante->asistencias()->where('estado', 'tardanza')->count();
        $totalAusencias = $estudiante->asistencias()->where('estado', 'ausencia')->count();

        return view('estudiantes.show', compact('estudiante', 'totalPresentes', 'totalTardanzas', 'totalAusencias'));
    }

    // Formulario de edición (EDIT)
    public function edit(Estudiante $estudiante)
    {
        $estudiante->load('tutores');
        return view('estudiantes.edit', compact('estudiante'));
    }

    // Actualizar datos del estudiante (UPDATE)
    public function update(Request $request, Estudiante $estudiante)
{
    // 1. Validar campos
    $request->validate([
    'nombres'          => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
    'apellidos'        => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
    'curso'            => ['required', 'integer', 'min:1', 'max:6'],
    'paralelo'         => ['nullable', 'in:A,B,C'],
    'estado'           => ['required', 'in:activo,inactivo,retirado'],
    'ci'               => ['nullable', 'regex:/^[0-9]+$/', 'max:20', 'unique:estudiantes,ci,' . $estudiante->id],
    'genero'           => ['nullable', 'in:M,F,Otro'],
    'fecha_nacimiento' => ['nullable', 'date'],

    'tutor_nombres'   => ['nullable', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
    'tutor_apellidos' => ['nullable', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
    'tutor_telefono'  => ['nullable', 'regex:/^[0-9]+$/'],
    'tutor_email'     => ['nullable', 'email', 'max:150'],
], [
    'nombres.regex'         => 'Los nombres solo deben contener letras.',
    'apellidos.regex'       => 'Los apellidos solo deben contener letras.',
    'curso.integer'         => 'El curso debe ser un número entre 1 y 6.',
    'curso.min'             => 'El curso debe ser entre 1 y 6.',
    'curso.max'             => 'El curso debe ser entre 1 y 6.',
    'paralelo.in'           => 'El paralelo solo puede ser A, B o C.',
    'ci.regex'              => 'El CI solo debe contener números.',
    'tutor_nombres.regex'   => 'Los nombres del tutor solo deben contener letras.',
    'tutor_apellidos.regex' => 'Los apellidos del tutor solo deben contener letras.',
    'tutor_telefono.regex'  => 'El teléfono solo debe contener números.',
]);

    // 2. Normalizar y actualizar estudiante
    $nombres   = $this->capitalizarNombre($request->nombres);
    $apellidos = $this->capitalizarNombre($request->apellidos);
    $paralelo  = $request->paralelo ? mb_strtoupper(trim($request->paralelo), 'UTF-8') : null;
    $curso     = trim($request->curso);

    $estudiante->update([
        'nombres'          => $nombres,
        'apellidos'        => $apellidos,
        'ci'               => $request->ci ? trim($request->ci) : null,
        'curso'            => (int) $request->curso, 
        'paralelo'         => $paralelo,
        'genero'           => $request->genero,
        'fecha_nacimiento' => $request->fecha_nacimiento,
        'estado'           => $request->estado,
    ]);

    // 3. Actualizar o vincular tutor
    if ($request->filled('tutor_nombres')) {
        $tutorData = [
            'nombres'   => $this->capitalizarNombre($request->tutor_nombres),
            'apellidos' => $this->capitalizarNombre($request->tutor_apellidos),
            'telefono'  => $request->tutor_telefono ? trim($request->tutor_telefono) : null,
            'email'     => $request->tutor_email ? trim($request->tutor_email) : null,
        ];

        if ($request->filled('tutor_id')) {
            Tutor::where('id', $request->tutor_id)->update($tutorData);
        } else {
            $plainPassword = Str::random(8);
            $tutorData['password'] = Hash::make($plainPassword);
            $tutorData['clave_inicial'] = $plainPassword;
            $tutorData['estado'] = 'activo';

            $nuevoTutor = Tutor::create($tutorData);
            $estudiante->tutores()->attach($nuevoTutor->id, [
                'parentesco' => $request->tutor_parentesco ?? 'Tutor',
            ]);
        }
    }

    // 4. Generar QR si no existe o si se pidió regenerar
    if (empty($estudiante->qr_imagen) || $request->has('regenerar_qr')) {

        if ($estudiante->qr_imagen && Storage::disk('public')->exists($estudiante->qr_imagen)) {
            Storage::disk('public')->delete($estudiante->qr_imagen);
        }

        $qrData = $estudiante->codigo_estudiante . '|' . $nombres . ' ' . $apellidos . '|' . $curso;
        $nombreArchivo = 'qr/' . $estudiante->codigo_estudiante . '.png';

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->size(300)
            ->margin(10)
            ->build();

        Storage::disk('public')->put($nombreArchivo, $result->getString());

        $estudiante->update([
            'qr_data'   => $qrData,
            'qr_imagen' => $nombreArchivo,
        ]);
    }

    return redirect()->route('estudiantes.show', $estudiante)
        ->with('success', 'Los datos del estudiante se actualizaron correctamente.');
}

    // Método para Generar/Forzar QR desde la vista de detalle
    public function generarQrManual(Estudiante $estudiante)
    {
        if ($estudiante->qr_imagen && Storage::disk('public')->exists($estudiante->qr_imagen)) {
            Storage::disk('public')->delete($estudiante->qr_imagen);
        }

        $qrData = $estudiante->codigo_estudiante . '|' . $estudiante->nombres . ' ' . $estudiante->apellidos . '|' . $estudiante->curso;
        $nombreArchivo = 'qr/' . $estudiante->codigo_estudiante . '.png';

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->size(300)
            ->margin(10)
            ->build();

        Storage::disk('public')->put($nombreArchivo, $result->getString());

        $estudiante->update([
            'qr_data'   => $qrData,
            'qr_imagen' => $nombreArchivo,
        ]);

        return back()->with('success', 'Código QR generado correctamente.');
    }

    // Eliminar registro (DESTROY)
    public function destroy(Estudiante $estudiante)
    {
        if ($estudiante->qr_imagen && Storage::disk('public')->exists($estudiante->qr_imagen)) {
            Storage::disk('public')->delete($estudiante->qr_imagen);
        }

        $estudiante->delete();

        return redirect()->route('estudiantes.index')
            ->with('success', 'Estudiante eliminado correctamente.');
    }

    // Activar / Inhabilitar
    public function toggleEstado(Estudiante $estudiante)
    {
        $estudiante->estado = $estudiante->estado === 'activo' ? 'inactivo' : 'activo';
        $estudiante->save();

        return redirect()->route('estudiantes.index')
            ->with('success', 'Estado del estudiante actualizado correctamente.');
    }

    /**
 * Capitaliza nombres: "juan carlos" → "Juan Carlos"
 */
private function capitalizarNombre(?string $texto): ?string
{
    if (!$texto) {
        return $texto;
    }

    $texto = trim(mb_strtolower($texto, 'UTF-8'));

    return collect(preg_split('/\s+/', $texto))
        ->filter()
        ->map(fn ($parte) => mb_convert_case($parte, MB_CASE_TITLE, 'UTF-8'))
        ->implode(' ');
}
}