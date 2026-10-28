<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Estudiante;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
    $request->validate($this->reglasEstudiante() + [
        // Tutor: entra a la app con su CI o su correo, al menos uno es obligatorio
        'tutor_nombres'   => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
        'tutor_apellidos' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
        'tutor_ci'        => ['nullable', 'required_without:tutor_email', 'digits_between:5,10'],
        'tutor_telefono'  => ['nullable', 'digits_between:7,8'],
        'tutor_email'     => ['nullable', 'required_without:tutor_ci', 'email', 'max:150'],
        'parentesco'      => ['nullable', 'string', 'max:50'],
    ], $this->mensajes());

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

    // Buscar tutor por CI o email (si ya existe, el estudiante se vincula a él)
    $porCi    = $tutorCi ? Tutor::where('ci', $tutorCi)->first() : null;
    $porEmail = $tutorEmail ? Tutor::where('email', $tutorEmail)->first() : null;

    if ($porCi && $porEmail && $porCi->id !== $porEmail->id) {
        return back()
            ->withErrors(['tutor_email' => "Ese correo ya pertenece a otro tutor ({$porEmail->nombres} {$porEmail->apellidos})."])
            ->withInput();
    }

    $tutor = $porCi ?? $porEmail;

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
        $totalAusencias = $estudiante->asistencias()->where('estado', 'ausente')->count();

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
    // 1. Validar campos (el tutor, si se envía, debe ser uno de este estudiante)
    $tutorId = $request->filled('tutor_id') ? (int) $request->tutor_id : null;

    $request->validate($this->reglasEstudiante($estudiante) + [
        'estado'          => ['required', 'in:activo,inactivo,retirado'],
        'tutor_id'        => ['nullable', 'integer', Rule::exists('estudiante_tutor', 'tutor_id')->where('estudiante_id', $estudiante->id)],
        'tutor_nombres'   => ['nullable', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
        'tutor_apellidos' => ['nullable', 'required_with:tutor_nombres', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
        'tutor_ci'        => ['nullable', 'digits_between:5,10', Rule::unique('tutores', 'ci')->ignore($tutorId)],
        'tutor_telefono'  => ['nullable', 'digits_between:7,8'],
        'tutor_email'     => ['nullable', 'email', 'max:150', Rule::unique('tutores', 'email')->ignore($tutorId)],
    ], $this->mensajes());

    // El tutor entra a la app con su CI o su correo: sin ninguno de los dos no podría hacerlo
    if ($request->filled('tutor_nombres') && !$request->filled('tutor_ci') && !$request->filled('tutor_email')) {
        return back()
            ->withErrors(['tutor_ci' => 'Ingrese el CI o el correo del tutor: los usa para entrar a la app.'])
            ->withInput();
    }

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
            'ci'        => $request->tutor_ci ? trim($request->tutor_ci) : null,
            'telefono'  => $request->tutor_telefono ? trim($request->tutor_telefono) : null,
            'email'     => $request->tutor_email ? trim($request->tutor_email) : null,
        ];

        if ($tutorId) {
            Tutor::where('id', $tutorId)->update($tutorData);
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

    public function credencialesTutor(Estudiante $estudiante)
{
    $estudiante->load('tutores');
    $tutor = $estudiante->tutores->first();

    if (!$tutor) {
        return redirect()
            ->route('estudiantes.show', $estudiante)
            ->with('success', 'Este estudiante no tiene tutor asignado.');
    }

    if (empty($tutor->clave_inicial)) {
        return redirect()
            ->route('estudiantes.show', $estudiante)
            ->with('success', 'El tutor no tiene clave inicial guardada.');
    }

    return redirect()
        ->route('estudiantes.show', $estudiante)
        ->with('ver_credenciales_tutor', [
            'estudiante_id' => $estudiante->id,
            'tutor_nombre'  => $tutor->nombres . ' ' . $tutor->apellidos,
            'usuario'       => $tutor->ci ?? $tutor->email,
            'password'      => $tutor->clave_inicial,
            'estudiante'    => $estudiante->nombres . ' ' . $estudiante->apellidos,
            'codigo_est'    => $estudiante->codigo_estudiante,
        ]);
}

    public function credencialesTutorPdf(Estudiante $estudiante)
    {
        $estudiante->load('tutores');
        $tutor = $estudiante->tutores->first();

        if (!$tutor || empty($tutor->clave_inicial)) {
            return redirect()
                ->route('estudiantes.show', $estudiante)
                ->with('success', 'No hay credenciales de tutor para descargar.');
        }

        $data = [
            'tutor_nombre' => $tutor->nombres . ' ' . $tutor->apellidos,
            'usuario'      => $tutor->ci ?? $tutor->email,
            'password'     => $tutor->clave_inicial,
            'estudiante'   => $estudiante->nombres . ' ' . $estudiante->apellidos,
            'codigo_est'   => $estudiante->codigo_estudiante,
            'fecha'        => now()->format('d/m/Y H:i'),
        ];

        $nombreArchivo = 'Credenciales_Tutor_' .
            str_replace(' ', '_', $tutor->nombres . '_' . $tutor->apellidos) .
            '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('estudiantes.credenciales_tutor_pdf', $data);

        return $pdf->download($nombreArchivo);
    }

    /**
     * Credenciales QR para imprimir y recortar (tamaño carnet, 8 por hoja carta):
     * de un curso/paralelo, de todos los activos o de un solo estudiante.
     */
    public function credencialesQrPdf(Request $request)
    {
        $datos = $request->validate([
            'curso'      => ['nullable', 'integer', 'min:1', 'max:6'],
            'paralelo'   => ['nullable', 'in:A,B,C'],
            'estudiante' => ['nullable', 'integer', 'exists:estudiantes,id'],
        ]);

        $estudiantes = Estudiante::where('estado', 'activo')
            ->when($datos['estudiante'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->when($datos['curso'] ?? null, fn ($q, $curso) => $q->where('curso', $curso))
            ->when($datos['paralelo'] ?? null, fn ($q, $paralelo) => $q->where('paralelo', $paralelo))
            ->orderBy('curso')->orderBy('paralelo')->orderBy('apellidos')->orderBy('nombres')
            ->get();

        if ($estudiantes->isEmpty()) {
            return back()->withErrors(['credenciales' => 'No hay estudiantes activos en ese curso y paralelo.']);
        }

        // El QR se genera en el momento con el contenido actual del estudiante (el que lee la cámara)
        $tarjetas = $estudiantes->map(fn ($e) => [
            'nombres'   => $e->nombres,
            'apellidos' => $e->apellidos,
            'codigo'    => $e->codigo_estudiante,
            'curso'     => $e->curso . '° ' . $e->paralelo,
            'qr'        => 'data:image/png;base64,' . base64_encode(
                Builder::create()->writer(new PngWriter())->data($e->qr_data ?: $e->codigo_estudiante)
                    ->size(300)->margin(10)->build()->getString()
            ),
        ]);

        $rutaLogo = public_path('images/Rene_Barrientos_Ortuno.jpg');
        $logo = is_file($rutaLogo) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($rutaLogo)) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('estudiantes.credenciales_qr_pdf', [
            'filas'   => $tarjetas->chunk(2),
            'colegio' => Configuracion::colegio(true),
            'gestion' => now()->year,
            'logo'    => $logo,
        ])->setPaper('letter');

        $nombre = 'Credenciales_QR'
            . (isset($datos['estudiante']) ? '_' . $estudiantes->first()->codigo_estudiante : '')
            . (isset($datos['curso']) ? '_' . $datos['curso'] . ($datos['paralelo'] ?? '') : '')
            . '.pdf';

        // Se abre en el navegador, listo para imprimir
        return $pdf->stream($nombre);
    }

    /**
     * Reglas del estudiante, iguales al registrar y al editar.
     * El paralelo es obligatorio: sin él no aparece en listas, horarios ni exámenes.
     */
    private function reglasEstudiante(?Estudiante $estudiante = null): array
    {
        return [
            'nombres'          => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
            'apellidos'        => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u'],
            'ci'               => ['nullable', 'digits_between:5,10', Rule::unique('estudiantes', 'ci')->ignore($estudiante?->id)],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today', 'after:' . now()->subYears(30)->toDateString()],
            'genero'           => ['nullable', 'in:M,F,Otro'],
            'curso'            => ['required', 'integer', 'min:1', 'max:6'],
            'paralelo'         => ['required', 'in:A,B,C'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombres.regex'                   => 'Los nombres del estudiante solo deben contener letras.',
            'apellidos.regex'                 => 'Los apellidos del estudiante solo deben contener letras.',
            'ci.digits_between'               => 'El CI del estudiante debe tener entre 5 y 10 números.',
            'ci.unique'                       => 'Ya existe un estudiante con ese CI.',
            'fecha_nacimiento.before'         => 'La fecha de nacimiento no puede ser hoy ni una fecha futura.',
            'fecha_nacimiento.after'          => 'Revise la fecha de nacimiento: el estudiante tendría más de 30 años.',
            'curso.required'                  => 'El curso es obligatorio.',
            'curso.integer'                   => 'El curso debe ser un número entre 1 y 6.',
            'curso.min'                       => 'El curso debe ser entre 1 y 6.',
            'curso.max'                       => 'El curso debe ser entre 1 y 6.',
            'paralelo.required'               => 'El paralelo es obligatorio.',
            'paralelo.in'                     => 'El paralelo solo puede ser A, B o C.',
            'tutor_nombres.regex'             => 'Los nombres del tutor solo deben contener letras.',
            'tutor_apellidos.regex'           => 'Los apellidos del tutor solo deben contener letras.',
            'tutor_apellidos.required_with'   => 'Ingrese los apellidos del tutor.',
            'tutor_ci.required_without'       => 'Ingrese el CI o el correo del tutor: los usa para entrar a la app.',
            'tutor_email.required_without'    => 'Ingrese el CI o el correo del tutor: los usa para entrar a la app.',
            'tutor_ci.digits_between'         => 'El CI del tutor debe tener entre 5 y 10 números.',
            'tutor_ci.unique'                 => 'Ese CI ya pertenece a otro tutor.',
            'tutor_email.unique'              => 'Ese correo ya pertenece a otro tutor.',
            'tutor_telefono.digits_between'   => 'El teléfono debe tener 7 u 8 números.',
            'tutor_id.exists'                 => 'El tutor no corresponde a este estudiante.',
        ];
    }

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