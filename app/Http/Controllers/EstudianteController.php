<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

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
            // Datos del tutor
            'tutor_nombres'   => 'required|string|max:100',
            'tutor_apellidos' => 'required|string|max:100',
            'tutor_ci'        => 'nullable|string|max:20',
            'tutor_telefono'  => 'nullable|string|max:20',
            'tutor_email'     => 'nullable|email|max:150',

            // Datos del estudiante
            'nombres'          => 'required|string|max:100',
            'apellidos'        => 'required|string|max:100',
            'ci'               => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'genero'           => 'nullable|in:M,F,Otro',
            'curso'            => 'required|string|max:50',
            'paralelo'         => 'nullable|string|max:10',
            'parentesco'       => 'nullable|string|max:50',
        ]);

        // 1. Crear tutor
        $tutor = Tutor::create([
            'nombres'   => $request->tutor_nombres,
            'apellidos' => $request->tutor_apellidos,
            'ci'        => $request->tutor_ci,
            'telefono'  => $request->tutor_telefono,
            'email'     => $request->tutor_email,
            'estado'    => 'activo',
        ]);

        // 2. Generar código único del estudiante
        $ultimoId = Estudiante::max('id') ?? 0;
        $codigo = 'EST-' . date('Y') . '-' . str_pad($ultimoId + 1, 4, '0', STR_PAD_LEFT);

        // 3. Datos que irán en el QR
        $qrData = $codigo . '|' . $request->nombres . ' ' . $request->apellidos . '|' . $request->curso;

        // 4. Crear estudiante
        $estudiante = Estudiante::create([
            'codigo_estudiante' => $codigo,
            'nombres'           => $request->nombres,
            'apellidos'         => $request->apellidos,
            'ci'                => $request->ci,
            'fecha_nacimiento'  => $request->fecha_nacimiento,
            'genero'            => $request->genero,
            'curso'             => $request->curso,
            'paralelo'          => $request->paralelo,
            'qr_data'           => $qrData,
            'estado'            => 'activo',
        ]);

        // 5. Relacionar estudiante con tutor
        $estudiante->tutores()->attach($tutor->id, [
            'parentesco'   => $request->parentesco ?? 'Tutor',
            'es_principal' => true,
        ]);

        // 6. Generar imagen del QR (PNG)
        $nombreArchivo = 'qr/' . $codigo . '.png';

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->size(300)
            ->margin(10)
            ->build();

        Storage::disk('public')->put($nombreArchivo, $result->getString());

        // 7. Actualizar la ruta de la imagen en el estudiante
        $estudiante->update([
            'qr_imagen' => $nombreArchivo,
        ]);

        return redirect()->route('estudiantes.index')
            ->with('success', 'Estudiante registrado correctamente. Código: ' . $codigo);
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
            'nombres'   => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'curso'     => 'required|string|max:50',
            'estado'    => 'required|in:activo,inactivo,retirado',
            'ci'        => 'nullable|string|max:20|unique:estudiantes,ci,'.$estudiante->id,
        ]);

        // 2. Actualizar datos del estudiante
        $estudiante->update($request->only([
            'nombres', 'apellidos', 'ci', 'curso', 'paralelo', 'genero', 'fecha_nacimiento', 'estado'
        ]));

        // 3. Actualizar o Vincular Datos del Tutor
        if ($request->filled('tutor_nombres')) {
            $tutorData = [
                'nombres'   => $request->tutor_nombres,
                'apellidos' => $request->tutor_apellidos,
                'telefono'  => $request->tutor_telefono,
                'email'     => $request->tutor_email,
            ];

            if ($request->filled('tutor_id')) {
                Tutor::where('id', $request->tutor_id)->update($tutorData);
            } else {
                $nuevoTutor = Tutor::create($tutorData);
                $estudiante->tutores()->attach($nuevoTutor->id, ['parentesco' => $request->tutor_parentesco ?? 'Tutor']);
            }
        }

        // 4. Generar QR si NO existe O si el usuario solicitó regenerarlo
        if (empty($estudiante->qr_imagen) || $request->has('regenerar_qr')) {
            
            // Borrar archivo anterior si existía
            if ($estudiante->qr_imagen && Storage::disk('public')->exists($estudiante->qr_imagen)) {
                Storage::disk('public')->delete($estudiante->qr_imagen);
            }

            // Reconstruir la información que tendrá el QR con los nuevos datos
            $qrData = $estudiante->codigo_estudiante . '|' . $request->nombres . ' ' . $request->apellidos . '|' . $request->curso;
            $nombreArchivo = 'qr/' . $estudiante->codigo_estudiante . '.png';

            // Generar la imagen con Endroid QrCode
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($qrData)
                ->size(300)
                ->margin(10)
                ->build();

            Storage::disk('public')->put($nombreArchivo, $result->getString());

            // Actualizar la base de datos
            $estudiante->update([
                'qr_data'   => $qrData,
                'qr_imagen' => $nombreArchivo,
            ]);
        }

        return redirect()->route('estudiantes.show', $estudiante)
            ->with('success', 'Los datos del estudiante se actualizaron correctamente.');
    }

    // Método para Generar/Forzar QR desde la vista de detalle o un botón (OPCIONAL/ÚTIL)
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
        // Borrar imagen QR del disco
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
}